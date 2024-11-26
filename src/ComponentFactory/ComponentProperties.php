<?php

declare(strict_types=1);

namespace Core\View\ComponentFactory;

use Stringable;
use Core\View\Component\ComponentInterface;

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
     * @param string[]                         $tags
     * @param array<string, ?string[]>         $tagged
     */
    public function __construct(
        public string $name,
        public string $class,
        public bool   $static,
        public array  $tags = [],
        public array  $tagged = [],
    ) {
    }

    public function __toString() : string
    {
        return $this->name;
    }
}
