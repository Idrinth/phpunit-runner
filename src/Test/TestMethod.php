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

class TestMethod implements Test
{
    /**
     * @var string
     */
    private $sourceFile;

    /**
     * @var string
     */
    private $className;

    /**
     * @var string
     */
    private $methodName;

    public function __construct(string $sourceFile, string $className, string $methodName)
    {
        $this->sourceFile = $sourceFile;
        $this->className  = $className;
        $this->methodName = $methodName;
    }

    public function sourceFile(): string
    {
        return $this->sourceFile;
    }

    public function className(): string
    {
        return $this->className;
    }

    public function methodName(): string
    {
        return $this->methodName;
    }
}