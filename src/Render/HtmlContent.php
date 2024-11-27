<?php

declare(strict_types=1);

namespace Core\View\Render;

use Latte\Runtime\HtmlStringable;
use Northrook\HTML\Element;
use Stringable;

final class HtmlContent implements HtmlStringable
{
    private array $content;

    public function __construct(
        null|Stringable|string|array $content,
        private bool                 $perfect = true,
    ) {
        // Is $content empty?
        if ( empty( \is_array( $content ) ? $content : (string) $content ) ) {
            $this->content = [];
            return;
        }

        if ( ! \is_array( $content ) ) {
            $content = [(string) $content];
        }

        $this->content = $content;
    }

    public function __toString() : string
    {
        return \implode( '', $this->content );
    }

    public static function toArray( null|Stringable|string|array $content ) : array
    {
        $content = new self( $content );
        return (array) $content->parse();
    }

    public function render() : string
    {
        $string = '';

        return \implode( ' ', $this->content );
    }

    public function parse() : self
    {
        if ( $this->perfect ) {
            $this->parseContent();
        }

        return $this;
    }

    protected function compressContent( ?array $array = null ) : array
    {
        // Grab $this->content for initial loop
        $array ??= $this->content;
        $content = [];

        foreach ( $array as $value ) {
            if ( \is_array( $value ) ) {
                // if ( \isset( $value['content'] ) ) {}
                $value = $this->compressContent( $value );
            }
            // dump( $value );
        }

        return $this->content = $content;
    }

    /**
     * String is returned during recursion.
     * Array returned upon completion.
     *
     * @param null|array<array-key, mixed> $array 🔁 recursive
     * @param null|int|string              $key   🔂 recursive
     *
     * @return array<array-key, mixed>|string
     */
    protected function parseContent( ?array $array = null, null|string|int $key = null ) : string|array
    {
        // Grab $this->content for initial loop
        $array ??= $this->content;
        $tag        = null;
        $attributes = [];

        // If $key is string, this iteration is an element
        if ( \is_string( $key ) ) {
            $tag        = \strrchr( $key, ':', true );
            $attributes = $array['attributes'];
            $array      = $array['content'];

            // if ( \str_ends_with( $tag, 'icon' ) && $get = $attributes['get'] ?? null ) {
            //     unset( $attributes['get'] );
            //     return (string) new Icon( $tag, $get, $attributes );
            // }
        }

        $content = [];

        foreach ( $array as $elementKey => $value ) {
            $elementKey = $this->nodeKey( $elementKey, \gettype( $value ) );

            if ( \is_array( $value ) ) {
                $content[$elementKey] = $this->parseContent( $value, $elementKey );
            }
            else {
                self::appendTextString( $value, $content );
            }
        }

        if ( $tag ) {
            $element = new Element( $tag, $attributes, $content );

            return $element->__toString();
        }

        return $this->content = $content;
    }

    protected function nodeKey( string|int $node, string $valueType ) : string|int
    {
        if ( \is_int( $node ) ) {
            return $node;
        }

        $index = \strrpos( $node, ':' );

        // Treat parsed string variables as simple strings
        if ( false !== $index && 'string' === $valueType && \str_starts_with( $node, '$' ) ) {
            return (int) \substr( $node, $index++ );
        }

        return $node;
    }

    protected function appendTextString( string $value, array &$content ) : void
    {
        // Trim $value, and bail early if empty
        if ( ! $value = \trim( $value ) ) {
            return;
        }

        $lastIndex = \array_key_last( $content );
        $index     = \count( $content );

        if ( \is_int( $lastIndex ) ) {
            if ( $index > 0 ) {
                $index--;
            }
        }

        if ( isset( $content[$index] ) ) {
            $content[$index] .= " {$value}";
        }
        else {
            $content[$index] = $value;
        }
    }
}
