<?php

namespace Core\View;

use Core\View\Component\{Attributes, InnerContent, TagInterface};
use Core\View\Template\TemplateCompiler;
use Exception\NotImplementedException;
use Northrook\HTML\Element\Tag;
use Northrook\Logger\Log;
use Throwable;
use function Cache\memoize;
use function Support\classBasename;
use InvalidArgumentException;
use const Cache\EPHEMERAL;
use BadFunctionCallException;

abstract class Component implements ComponentInterface
{
    /** @var ?string Define a name for this component */
    protected const ?string NAME = null;

    /** @var ?string The default tag for this component */
    protected const ?string TAG = null;

    public readonly string $uniqueId;

    /** @var string Render cache */
    protected string $html;

    public readonly string $name;

    public array|Attributes $attributes;

    /**
     * @param TemplateCompiler $compiler
     *
     * @return string
     */
    abstract protected function compile( TemplateCompiler $compiler ) : string;

    /**
     * Process arguments passed to the {@see self::create()} method.
     *
     * @param array $arguments
     *
     * @return void
     */
    protected function parseArguments( array &$arguments ) : void
    {
    }

    final public function create(
        array   $arguments,
        array   $promote = [],
        ?string $uniqueId = null,
    ) : self {
        $this->promoteTagProperties( $arguments, $promote );
        $this->parseArguments( $arguments );

        if ( $this::TAG ) {
            if ( ! $this instanceof TagInterface ) {
                $message = 'The '.$this::class.' must implement the TagInterface when using a tag.';
                throw new NotImplementedException( $message, TagInterface::class );
            }

            \assert( \property_exists( $this, 'tag' ) );

            $this->tag = (string) new Tag( $arguments['tag'] ?? $this::TAG );
        }

        $this->name ??= $this::componentName();

        if ( \method_exists( $this, 'setContent' ) ) {
            dump( $this::class );
            $this->setContent( ...$arguments['content'] ?? [] );
            unset( $arguments['content'] );
        }

        if ( ! empty( $arguments['content'] ) ) {
            // if ( isset( $arguments['content'] ) && ! empty( $arguments['content'] ) ) {
            $class        = $this::class;
            $innerContent = InnerContent::class;
            throw new InvalidArgumentException( <<<MSG
                The {$class} must use the {$innerContent} trait when passed \$arguments[content].
                MSG, );
        }

        $this->attributes = new Attributes( $arguments['attributes'] ?? [] );

        $this->setComponentUniqueId(
            $uniqueId ?? \serialize( [$arguments] ).\spl_object_id( $this->attributes ),
        );

        unset( $arguments['tag'], $arguments['attributes'], $arguments['content'], $uniqueId );

        foreach ( $arguments as $property => $value ) {
            if ( \property_exists( $this, $property ) && ! isset( $this->{$property} ) ) {
                $this->{$property} = $value;

                continue;
            }

            if ( \method_exists( $this, $value ) ) {
                $this->{$value}();
            }

            Log::error(
                'The {component} was provided with undefined property {property}.',
                ['component' => $this->name, 'property' => $property],
            );
        }
        return $this;
    }

    final public function __toString()
    {
        return $this->render();
    }

    final public function render( ?TemplateCompiler $compiler = null ) : ?string
    {
        if ( ! isset( $this->uniqueId ) ) {
            $message = 'The '.$this::class.'->build(...) must be called before rendering.';
            throw new BadFunctionCallException( $message );
        }
        try {
            $this->attributes = (array) $this->attributes->getAttributes();
            return $this->html ??= $this->compile( $compiler ?? new TemplateCompiler() );
        }
        catch ( Throwable $exception ) {
            dump( $exception );
            Log::exception( $exception );
            return null;
        }
    }

    final public function hasBuildStep() : bool
    {
        return \method_exists( $this, 'build' );
    }

    final public static function componentName() : string
    {
        $name = self::NAME ?? static::class;
        return memoize(
            static function() use ( $name ) {
                $name = \strtolower( classBasename( $name ) );

                if ( ! $name || ! \preg_match( '/^[a-z0-9:]+$/', $name ) ) {
                    $message = 'The name must be lower-case alphanumeric.';

                    if ( \is_numeric( $name[0] ) ) {
                        $message = 'The name cannot start with a number.';
                    }

                    if ( \str_starts_with( $name, ':' ) || \str_ends_with( $name, ':' ) ) {
                        $message = 'The name must not start or end with a separator.';
                    }

                    throw new InvalidArgumentException( $name );
                }

                return $name;
            },
            $name,
            EPHEMERAL,
        );
    }

    final protected function setComponentUniqueId( ?string $hash = null ) : void
    {
        if ( \strlen( $hash ) === 16 && \ctype_alnum( $hash ) ) {
            $this->uniqueId ??= \strtolower( $hash );
            return;
        }
        $this->uniqueId ??= \hash( algo : 'xxh3', data : $hash );
    }

    /**
     * @param array                    $arguments
     * @param array<string, ?string[]> $promote
     *
     * @return void
     */
    private function promoteTagProperties( array &$arguments, array $promote = [] ) : void
    {
        $exploded         = \explode( ':', $arguments['tag'] );
        $arguments['tag'] = $exploded[0];

        $promote = $promote[$arguments['tag']] ?? null;

        foreach ( $exploded as $position => $tag ) {
            if ( $promote && ( $promote[$position] ?? false ) ) {
                $arguments[$promote[$position]] = $tag;
                unset( $arguments[$position] );

                continue;
            }
            if ( $position ) {
                $arguments[$position] = $tag;
            }
        }
    }
}
