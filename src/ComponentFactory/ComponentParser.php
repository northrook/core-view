<?php

declare(strict_types=1);

namespace Core\View\ComponentFactory;

use Core\Symfony\Console\Output;
use Core\View\Attribute\ViewComponent;
use Core\View\Component\ComponentInterface;
use Exception\NotImplementedException;
use Support\{ClassInfo, FileInfo, Reflect};

/**
 * @internal
 * @used-by \Core\View\Compiler\RegisterComponentPass
 */
final readonly class ComponentParser
{
    private ClassInfo $component;

    private ViewComponent $componentNode;

    /** @var non-empty-lowercase-string */
    public string $name;

    /** @var class-string<ComponentInterface> */
    public string $class;

    /** @var array<int, string> */
    public array $tags;

    public int $priority;

    public array $properties;

    public function __construct( string|ClassInfo $component )
    {
        $this->parse( $component );
        $this->validateComponent();
        $this->nodeAttribute();

        $this->class = $this->component->class;

        $this->name = $this->class::componentName();

        $this->tags = $this->componentNodeTags();

        $this->properties = (array) new ComponentProperties(
            $this->name,
            $this->class,
            $this->componentNode->static,
            $this->priority = $this->componentNode->priority,
            $this->tags,
            $this->taggedProperties(),
            $this->componentAssets(),
        );
    }

    protected function componentNodeTags() : array
    {
        $set = [];

        foreach ( $this->componentNode->tags as $tag ) {
            if ( ! $tag || \preg_match( '#[^a-z]#', $tag[0] ) ) {
                $reason = $tag ? null : 'Tags cannot be empty.';
                $reason ??= ':' === $tag[0] ? 'Tags cannot start with a separator.'
                        : 'Tags must start with a letter.';
                Output::error( 'Invalid component tag.', 'Value: '.$tag, $reason );

                continue;
            }

            if ( \str_contains( $tag, ':' ) ) {
                $fragments      = \explode( ':', $tag );
                $tag            = \array_shift( $fragments );
                $taggedFragment = false;

                foreach ( $fragments as $index => $fragment ) {
                    if ( \preg_match( '{[a-z]+}', $fragment ) ) {
                        $taggedFragment = true;
                    }

                    if ( $taggedFragment ) {
                        unset( $fragments[$index] );
                    }
                }
                $tag .= ':'.\implode( ':', $fragments );
            }

            $set[$tag] = $this->name;
        }

        return $set;
    }

    protected function taggedProperties() : array
    {
        $properties = [];

        foreach ( $this->componentNode->tags as $tag ) {
            $tags = \explode( ':', $tag );
            $tag  = $tags[0];

            foreach ( $tags as $position => $argument ) {
                if ( \str_contains( $argument, '{' ) ) {
                    $property = \trim( $argument, " \t\n\r\0\x0B{}" );

                    if ( $this->component->reflect()->hasProperty( $property ) ) {
                        $tags[$position] = $property;
                    }
                    else {
                        Output::error( "Property '{$property}' not found in component '{$this->name}'" );
                    }

                    continue;
                }

                if ( $position && ! $this->component->reflect()->hasMethod( $argument ) ) {
                    Output::error( "Method {$this->class}::{$argument}' not found in component '{$this->name}'" );
                }

                $tags[$position] = null;
            }

            $properties[$tag] = $tags;
        }

        return $properties;
    }

    /**
     * @param ClassInfo|string $component
     *
     * @return void
     */
    private function parse( string|ClassInfo $component ) : void
    {
        $this->component = $component instanceof ClassInfo ? $component : new ClassInfo( $component );
    }

    private function validateComponent() : void
    {
        if ( ! \is_subclass_of( $this->component->class, ComponentInterface::class ) ) {
            throw new NotImplementedException( $this->component->class, ComponentInterface::class );
        }
    }

    private function nodeAttribute() : void
    {
        $this->componentNode
                = Reflect::getAttribute( $this->component->reflect(), ViewComponent::class )
                  ?? new ViewComponent();
    }

    private function componentAssets() : array
    {
        $directory = \dirname( $this->component->fileInfo->getPathname() );
        $assets    = [];

        foreach ( \glob( $directory.'/*.{css,js}', GLOB_BRACE ) as $file ) {
            $file = new FileInfo( $file );
            if ( \str_starts_with( $file->getFilename(), $this->name ) ) {
                $assets[] = $file->getRealPath();
            }
        }

        return $assets;
    }
}
