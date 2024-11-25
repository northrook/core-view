<?php

declare(strict_types=1);

namespace Core\View\ComponentFactory;

use Stringable;

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
final readonly class ComponentProperties implements Stringable
{
    /**
     * @param non-empty-lowercase-string                            $name
     * @param class-string<\Core\View\Component\ComponentInterface> $class
     * @param 'live'|'runtime'|'static'                             $render
     * @param string[]                                              $tags
     * @param array<string, ?string[]>                              $tagged
     */
    public function __construct(
        public string $name,
        public string $class,
        public string $render,
        public array  $tags = [],
        public array  $tagged = [],
    ) {
    }

    public function __toString() : string
    {
        return $this->name;
    }
}
