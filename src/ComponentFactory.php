<?php

namespace Core\View;

use Core\View\Component\{ComponentInterface, NodeInterface};
use Core\View\ComponentFactory\ComponentProperties;
use Core\View\Exception\ComponentNotFoundException;
use Core\View\Template\Node\{ComponentNode};
use Core\View\Template\Compiler\NodeCompiler;
use Core\View\Template\{NodeParser, TemplateCompiler};
use Northrook\Logger\{Level, Log};
use Core\Symfony\DependencyInjection\{ServiceContainer, ServiceContainerInterface};
use Support\Arr;
use Symfony\Component\DependencyInjection\ServiceLocator;
use const Cache\AUTO;

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
     * @param ServiceLocator                                                                                                                                      $componentLocator
     */
    public function __construct(
        private readonly array          $components,
        private readonly array          $tags,
        private readonly ServiceLocator $componentLocator,
    ) {
    }

    /**
     * Renders a component at runtime.
     *
     * @param class-string|string  $component
     * @param array<string, mixed> $arguments
     * @param ?int                 $cache
     *
     * @return string
     */
    public function render( string $component, array $arguments = [], ?int $cache = AUTO ) : string
    {
        $properties = $this->getComponentProperties( $component );

        if ( ! $properties ) {
            Log::exception( new ComponentNotFoundException( $component ), Level::CRITICAL );
            return '';
        }

        $component = clone $this->getComponent( $component );
        $component->create( $arguments, $properties->tagged );

        if ( $component->hasBuildStep() ) {
            dump( 'Build step required.' );
        }

        $html = $component->render( $this->templateCompiler() );

        if ( ! $html ) {
            Log::exception( new ComponentNotFoundException( $component ), Level::CRITICAL );
            return '';
        }

        $this->instantiated[$component->name][] = $component->uniqueId;

        return $html;
    }

    /**
     * @param ComponentProperties|string $component
     * @param NodeCompiler               $nodeCompiler
     *
     * @return ComponentNode
     */
    public function getComponentNode(
        string|ComponentProperties $component,
        NodeParser                 $nodeCompiler,
    ) : ComponentNode {
        $component = $this->getComponent( $component );

        if ( ! $component instanceof NodeInterface ) {
            throw new ComponentNotFoundException( $component, 'The component "'.$component->name.'" does implement the NodeInterface');
        }
        return $component->node( $nodeCompiler );
    }

    /**
     * Begin the Build proccess of a component.
     *
     * @param class-string|ComponentProperties|string $component
     *
     * @return ComponentInterface
     */
    public function getComponent( string|ComponentProperties $component ) : ComponentInterface
    {
        $component = $this->getComponentName( (string) $component );

        dump( $component );

        if ( $this->componentLocator->has( $component ) ) {
            $component = $this->componentLocator->get( $component );

            \assert( $component instanceof ComponentInterface );

            return $component;
        }

        throw new ComponentNotFoundException( $component, 'Not found in the Component Container.' );
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
        dump( __METHOD__." {$from}" );
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

    private function templateCompiler() : ?TemplateCompiler
    {
        return $this->serviceLocator( TemplateCompiler::class, true );
    }
}
