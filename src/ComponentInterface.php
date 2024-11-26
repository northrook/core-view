<?php

declare(strict_types=1);

namespace Core\View;

use Core\Symfony\DependencyInjection\ServiceContainer;
use Core\View\Template\Compiler\NodeCompiler;
use Core\View\Template\TemplateCompiler;
use Stringable;

// The __constructor sort has to be a set standard
// We could have an abstract static for 'default' initialization?

/**
 * Dependency Injection
 * - Apply the {@see Autowire} attribute on public properties
 * - The optional {@see self::build()} method is called after {@see self::create()}, but before {@see self::render()}.
 *
 * @property-read string $name
 * @property-read string $uniqueId
 * @method void build()
 */
interface ComponentInterface extends Stringable
{
    /**
     * Creates a new {@see ComponentInterface} object using the provided `$arguments`.
     *
     * @param array{tag: ?string, attributes: array<string,mixed>, content: string[] } $arguments
     * @param array<string, ?string[]>                                                 $promote
     * @param ?string                                                                  $uniqueId  [optional]
     *
     * @return self
     */
    public function create(
        array   $arguments,
        array   $promote = [],
        ?string $uniqueId = null,
    ) : self;

    /**
     * Renders the final component HTML.
     *
     * @param ?TemplateCompiler $compiler
     *
     * @return ?string null on failure
     */
    public function render( ?TemplateCompiler $compiler = null ) : ?string;

    /**
     * Check if this {@see ComponentInterface} has a build step.
     *
     * - Checks if the {@see self::build()} method exists.
     * - Passed `services` from the {@see ServiceContainer} will be autowired by the {@see ComponentFactory}.
     *
     * @return bool
     */
    public function hasBuildStep() : bool;

    public static function componentName() : string;
}
