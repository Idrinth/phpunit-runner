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

final class TestCollectionIterator implements \Iterator
{
    /**
     * @var Test[]
     */
    private $tests;

    /**
     * @var int
     */
    private $position;

    public function __construct(TestCollection $collection)
    {
        $this->tests = $collection->tests();
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return $this->position < \count($this->tests);
    }

    public function key(): int
    {
        return $this->position;
    }

    public function current(): Test
    {
        return $this->tests[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }
}
