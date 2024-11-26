<?php

declare(strict_types=1);

namespace Core\View\Component;

use Countable;
use Latte\Runtime\{Html, HtmlStringable};
use Stringable;
use Iterator;

/**
 * @internal
 */
final class Content implements Iterator, HtmlStringable, Countable
{
    private int $key;

    /** @var string[] */
    private array $content = [];

    public function __construct( null|string|Stringable ...$content )
    {
        $this->append( ...$content );
        $this->key = 0;
    }

    public function __toString() : string
    {
        $string        = \implode( PHP_EOL, $this->content );
        $this->content = [];
        return $string;
    }

    public function current() : HtmlStringable
    {
        return new Html( $this->content[$this->key] );
    }

    public function count() : int
    {
        return \count( $this->content );
    }

    public function set( null|string|Stringable ...$content ) : void
    {
        $this->content = $content;
    }

    public function prepend( null|string|Stringable ...$content ) : void
    {
        foreach ( $content as $item ) {
            \array_unshift( $this->content, (string) $item );
        }
    }

    public function append( null|string|Stringable ...$content ) : void
    {
        foreach ( $content as $item ) {
            $this->content[] = (string) $item;
        }
    }

    public function key() : int
    {
        return $this->key;
    }

    public function next() : void
    {
        unset( $this->content[$this->key] );
        $this->key++;
    }

    public function rewind() : void
    {
        $this->key = 0;
    }

    public function valid() : bool
    {
        return isset( $this->content[$this->key] );
    }
}
