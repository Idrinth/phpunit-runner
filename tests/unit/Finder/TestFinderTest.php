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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\NewRunner\TestFixture\BarTest;
use PHPUnit\NewRunner\TestFixture\FooTest;
use PHPUnit\NewRunner\TestFixture\MyTestCase;
use PHPUnit\NewRunner\TestFixture\TestWithTest;

/**
 * @covers PHPUnit\NewRunner\TestFinder
 *
 * @uses PHPUnit\NewRunner\DataProvider
 * @uses PHPUnit\NewRunner\DataProviderCollection
 * @uses PHPUnit\NewRunner\TestCollection
 * @uses PHPUnit\NewRunner\TestCollectionIterator
 * @uses PHPUnit\NewRunner\TestMethod
 * @uses PHPUnit\NewRunner\TestMethodCollection
 * @uses PHPUnit\NewRunner\TestMethodWithDataProvider
 * @uses PHPUnit\NewRunner\TestMethodWithDependencies
 */
final class TestFinderTest extends TestCase
{
    /**
     * @var TestFinder
     */
    private $finder;

    /**
     * @var string
     */
    private $fixtureDirectory;

    protected function setUp(): void
    {
        /** @var Cache|MockObject $cache */
        $cache = $this->createMock(Cache::class);

        $this->finder = new TestFinder($cache);

        $this->fixtureDirectory = (string) \realpath(__DIR__ . '/../../_fixture');
    }

    public function testFindsTestMethods(): void
    {
        $tests = $this->finder->find([$this->fixtureDirectory]);

        $this->assertContains(
            new TestMethod(
                $this->fixtureDirectory . \DIRECTORY_SEPARATOR . 'FooTest.php',
                FooTest::class,
                'testOne',
                AnnotationCollection::fromArray(
                    new Annotation(
                        'covers',
                        'Foo'
                    )
                ),
                AnnotationCollection::fromArray()
            ),
            $tests,
            '',
            false,
            false
        );

        $this->assertContains(
            new TestMethod(
                $this->fixtureDirectory . \DIRECTORY_SEPARATOR . 'FooTest.php',
                FooTest::class,
                'testTwo',
                AnnotationCollection::fromArray(
                    new Annotation(
                        'covers',
                        'Foo'
                    )
                ),
                AnnotationCollection::fromArray(
                    new Annotation(
                        'depends',
                        'testOne'
                    )
                )
            ),
            $tests,
            '',
            false,
            false
        );

        $this->assertContains(
            new TestMethod(
                $this->fixtureDirectory . \DIRECTORY_SEPARATOR . 'FooTest.php',
                FooTest::class,
                'testThree',
                AnnotationCollection::fromArray(
                    new Annotation(
                        'covers',
                        'Foo'
                    )
                ),
                AnnotationCollection::fromArray(
                    new Annotation(
                        'dataProvider',
                        'dataProvider'
                    )
                )
            ),
            $tests,
            '',
            false,
            false
        );

        $this->assertNotContains(
            new TestMethod(
                $this->fixtureDirectory . \DIRECTORY_SEPARATOR . 'MyTestCase.php',
                MyTestCase::class,
                'testOne',
                AnnotationCollection::fromArray(
                    new Annotation(
                        'group',
                        'default'
                    )
                ),
                AnnotationCollection::fromArray()
            ),
            $tests,
            '',
            false,
            false
        );

        $this->assertContains(
            new TestMethod(
                $this->fixtureDirectory . \DIRECTORY_SEPARATOR . 'BarTest.php',
                BarTest::class,
                'testOne',
                AnnotationCollection::fromArray(
                    new Annotation(
                        'covers',
                        'Bar'
                    )
                ),
                AnnotationCollection::fromArray()
            ),
            $tests,
            '',
            false,
            false
        );

        $this->assertContains(
            new TestMethod(
                $this->fixtureDirectory . \DIRECTORY_SEPARATOR . 'BarTest.php',
                BarTest::class,
                'testTwo',
                AnnotationCollection::fromArray(
                    new Annotation(
                        'covers',
                        'Bar'
                    )
                ),
                AnnotationCollection::fromArray()
            ),
            $tests,
            '',
            false,
            false
        );

        $this->assertContains(
            new TestMethod(
                $this->fixtureDirectory . \DIRECTORY_SEPARATOR . 'TestWithTest.php',
                TestWithTest::class,
                'testSeven',
                AnnotationCollection::fromArray(),
                AnnotationCollection::fromArray(
                    new Annotation(
                        'testWith',
                        '[[1],[8],[12]]'
                    )
                )
            ),
            $tests,
            '',
            false,
            false
        );
    }
}
