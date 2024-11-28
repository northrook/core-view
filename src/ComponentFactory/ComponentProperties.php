<?php

declare(strict_types=1);

namespace Core\View\ComponentFactory;

use Core\View\Component\ComponentInterface;
use Stringable;

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
final readonly class ComponentProperties implements Stringable
{
    /**
     * @param non-empty-lowercase-string       $name
     * @param class-string<ComponentInterface> $class
     * @param bool                             $static
     * @param int                              $priority
     * @param string[]                         $tags
     * @param array<string, ?string[]>         $tagged
     */
    public function __construct(
        public string $name,
        public string $class,
        public bool   $static,
        public int    $priority = 0,
        public array  $tags = [],
        public array  $tagged = [],
    ) {
    }

    public function __toString() : string
    {
        return $this->name;
    }

    public function targetTag( string $tag ) : bool
    {
        // Parsed namespaced $tag
        if ( \str_contains( $tag, ':' ) ) {
            if ( \str_starts_with( $tag, 'ui:' ) ) {
                $tag = \substr( $tag, 3 );
            }
            $tag = \strstr( $tag, ':', true ) ?: $tag;

            $tag .= ':';
        }
        return \array_key_exists( $tag, $this->tags );
    }
}
