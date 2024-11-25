<?php

namespace Core\View\Template;

use Core\View\Template\Compiler\ViewLoader;
use LogicException;
use Latte\{Engine, Loader, Loaders\FileLoader};
use Northrook\Filesystem\File;
use Northrook\Logger\Log;
use Override;

final class TemplateCompiler implements TemplateCompilerInterface
{
    private readonly Engine $engine;

    private readonly ViewLoader $loader;

    public function __construct(
        ViewLoader|string|array $viewDirectories = [],
        protected ?string       $cacheDirectory = null,
        protected string        $locale = 'en',
        public bool             $autoRefresh = true,
        private readonly array  $extensions = [],
        private readonly array  $variables = [],
    ) {
        if ( $viewDirectories instanceof ViewLoader ) {
            $this->loader = $viewDirectories;
        }
        else {
            $this->loader = new ViewLoader( $viewDirectories );
        }
    }

    #[Override]
    public function render(
        string       $view,
        object|array $parameters = [],
        ?string      $block = null, // TODO : See what this actually does
        ?Loader      $loader = null,
        bool         $cache = true,
    ) : string {
        $engine = $this->engine( $loader );

        if ( ! $cache ) {
            $engine->setTempDirectory( null );
        }
        $render = $engine->renderToString(
            $this->loader->get( $view ),
            $this->global( $parameters ),
            $block,
        );

        if ( ! $cache ) {
            $engine->setTempDirectory( $this->cacheDirectory );
        }

        return $render;
    }

    public function engine( ?Loader $loader = null ) : Engine
    {
        if ( $loader ) {
            return $this->startEngine( $loader );
        }

        return $this->engine ??= $this->startEngine();
    }

    private function startEngine( ?Loader $loader = null ) : Engine
    {
        if ( $this->cacheDirectory && ! \file_exists( $this->cacheDirectory ) ) {
            \mkdir( $this->cacheDirectory, 0777, true )
                    ?: throw new LogicException( "Unable to create cache directory '{$this->cacheDirectory}.'" );
        }

        // Initialize the Engine.
        $engine = new Engine();

        $loader ??= new FileLoader();

        // Add all registered extensions to the Engine.
        \array_map( [$engine, 'addExtension'], $this->extensions );

        $engine
            ->setTempDirectory( $this->cacheDirectory )
            ->setAutoRefresh( $this->autoRefresh )
            ->setLoader( $loader )
            ->setLocale( $this->locale );

        Log::info(
            'Started Latte Engine {id} using '.\strchr( $loader::class, '\\' ),
            [
                'id'     => \spl_object_id( $engine ),
                'engine' => $engine,
            ],
        );

        return $engine;
    }

    /**
     * Adds {@see Latte::$globalVariables} to all templates.
     *
     * - {@see $globalVariables} are not available when using Latte `templateType` objects.
     *
     * @param array<array-key,mixed>|object $parameters
     *
     * @return array<array-key,mixed>|object
     */
    private function global( object|array $parameters ) : object|array
    {
        if ( \is_object( $parameters ) ) {
            return $parameters;
        }

        return $this->variables + $parameters;
    }

    public function clearTemplateCache() : bool
    {
        return File::remove( $this->cacheDirectory );
    }

    public function pruneTemplateCache() : array
    {
        $templates = [];

        foreach ( \glob( $this->cacheDirectory.'/*.php' ) as $file ) {
            $templates[\basename( $file )] = $file;
        }

        Log::info(
            'Found {count} that could be pruned.',
            // 'Pruned {count} templates from cache.',
            [
                'count'  => \count( $templates ),
                'pruned' => $templates,
            ],
        );

        return $templates;
    }
}
