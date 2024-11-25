<?php

namespace Core\View;

use Core\View\ComponentFactory\ComponentProperties;
use Core\Symfony\DependencyInjection\{ServiceContainer, ServiceContainerInterface};
use Support\Arr;

final class ComponentFactory implements ServiceContainerInterface
{
    use ServiceContainer;

    /** @var array<string, ComponentProperties> */
    private array $propertiesCache = [];

    /**
     * `[ className => uniqueId ]`.
     *
     * @var array<class-string|string, array<int, string>>
     */
    private array $instantiated = [];

    /**
     * Provide a [class-string, args[]] array.
     *
     * @param array<class-string, array{name: string, class: class-string, render: 'live'|'runtime'|'static', tags: string[], tagged: array<string, ?string[]>} > $components
     * @param array                                                                                                                                               $tags
     */
    public function __construct(
        private readonly array $components,
        private readonly array $tags,
    ) {
    }


    public function build() : ComponentInterface
    {

    }


    /**
     * @return array<class-string, array<int, string>>
     */
    public function getInstantiated() : array
    {
        return $this->instantiated;
    }

    public function getRegisteredComponents() : array
    {
        return $this->components;
    }

    /**
     * Retrieve {@see ComponentProperties} by `name`, `className`, or `tag`.
     *
     * Returns `null` if the resulting component does not exist.
     *
     * @param string $from name or tag
     *
     * @return ?ComponentProperties
     */
    public function getComponentProperties( string $from ) : ?ComponentProperties
    {
        $component = $this->getComponentName( $from );

        if ( ! $component ) {
            return null;
        }

        return $this->propertiesCache[$component] ??= new ComponentProperties( ...$this->components[$component] );
    }

    /**
     * @param string $from name or tag
     *
     * @return null|string
     */
    public function getComponentName( string $from ) : ?string
    {
        // If the provided $value matches an array name, return it
        if ( \array_key_exists( $from, $this->components ) ) {
            return $from;
        }

        // If the $value is a class-string, the class exists, and is a component, return the name
        if ( \str_contains( $from, '\\' ) && \class_exists( $from ) ) {
            return Arr::search( $this->components, $from );
        }

        // Parsed namespaced tag $value
        if ( \str_contains( $from, ':' ) ) {
            if ( \str_starts_with( $from, 'ui:' ) ) {
                $from = \substr( $from, 3 );
            }

            $from = \strstr( $from, ':', true ) ?: $from;
        }

        return $this->tags[$from] ?? null;
    }

    /**
     * Check if the provided string matches any {@see ComponentFactory::$components}.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasComponent( string $name ) : bool
    {
        return \array_key_exists( $name, $this->components );
    }

    /**
     * Check if the provided string matches any {@see ComponentFactory::$tags}.
     *
     * @param string $tag
     *
     * @return bool
     */
    public function hasTag( string $tag ) : bool
    {
        return \array_key_exists( $tag, $this->tags );
    }


}
