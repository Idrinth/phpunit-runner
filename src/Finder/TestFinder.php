<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\NewRunner;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Exception\EmptyPhpSourceCode;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class TestFinder
{
    /**
     * @var Cache
     */
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @throws EmptyPhpSourceCode
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function find(array $directories): TestCollection
    {
        $tests = new TestCollection;

        foreach ($this->findTestFilesInDirectories($directories) as $file) {
            if ($this->cache->has($file->getRealPath())) {
                $testsInFile = $this->cache->get($file->getRealPath());
            } else {
                $testsInFile = $this->findTestsInFile($file);

                $this->cache->set($file->getRealPath(), $testsInFile);
            }

            $tests->addFrom($testsInFile);
        }

        return $tests;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function findTestFilesInDirectories(array $directories): Finder
    {
        $finder = new Finder;

        $finder->files()
               ->in($directories)
               ->name('*Test.php')
               ->sortByName();

        return $finder;
    }

    /**
     * @throws \RuntimeException
     * @throws EmptyPhpSourceCode
     */
    private function findTestsInFile(SplFileInfo $file): TestCollection
    {
        $tests = new TestCollection;

        foreach ($this->findClassesInFile($file) as $class) {
            if (!$this->isTestClass($class)) {
                continue;
            }

            $className             = $class->getName();
            $sourceFile            = $file->getRealPath();
            $classLevelAnnotations = $this->annotations($class->getDocComment());

            foreach ($class->getMethods() as $method) {
                if (!$this->isTestMethod($method)) {
                    continue;
                }

                $tests->add(
                    new TestMethod(
                        $sourceFile,
                        $className,
                        $method->getName(),
                        $classLevelAnnotations,
                        $this->annotations($method->getDocComment())
                    )
                );
            }
        }

        return $tests;
    }

    /**
     * @throws \RuntimeException
     * @throws EmptyPhpSourceCode
     *
     * @return ReflectionClass[]
     */
    private function findClassesInFile(SplFileInfo $file): array
    {
        $reflector = new ClassReflector($this->createSourceLocator($file->getContents()));

        return $reflector->getAllClasses();
    }

    /**
     * @throws EmptyPhpSourceCode
     */
    private function createSourceLocator(string $source): AggregateSourceLocator
    {
        $astLocator = (new BetterReflection())->astLocator();

        return new AggregateSourceLocator(
            [
                new StringSourceLocator($source, $astLocator),
                new AutoloadSourceLocator($astLocator),
                new PhpInternalSourceLocator($astLocator)
            ]
        );
    }

    private function isTestClass(ReflectionClass $class): bool
    {
        return !$class->isAbstract() && $class->isSubclassOf(TestCase::class);
    }

    private function isTestMethod(ReflectionMethod $method): bool
    {
        if (\strpos($method->getName(), 'test') !== 0) {
            return false;
        }

        if ($method->isAbstract() || !$method->isPublic()) {
            return false;
        }

        if ($method->getDeclaringClass()->getName() === Assert::class) {
            return false;
        }

        if ($method->getDeclaringClass()->getName() === TestCase::class) {
            return false;
        }

        return true;
    }

    private function annotations(string $docBlock): AnnotationCollection
    {
        $annotations = new AnnotationCollection;
        $docBlock    = \substr($docBlock, 3, -2);

        if (!$docBlock) {
            return $annotations;
        }

        if (\preg_match_all('/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?[ \t]*\r?$/m', $docBlock, $matches)) {
            $numMatches = \count($matches[0]);

            for ($i = 0; $i < $numMatches; ++$i) {
                if ($matches['name'][$i] === 'testWith') {
                    continue;
                }
                $annotations->add(
                    new Annotation(
                        (string) $matches['name'][$i],
                        (string) $matches['value'][$i]
                    )
                );
            }

            if (\preg_match_all('/@(?P<name>testWith)[\t ](?P<value>(\[.*?\]|[\n\r]+[\t ]*\*[\t ]+)+)/', $docBlock, $matches)) {
                foreach ($matches['value'] as $value) {
                    $annotations->add(
                        new Annotation(
                            'testWith',
                            '[' . \implode(',', \preg_split('/[ \t]*[\r\n][ \t]*\*[ \t]*/', $value) ?: []) . ']'
                        )
                    );
                }
            }
        }

        return $annotations;
    }
}
