<?php

declare(strict_types=1);

namespace Core\View\Template\Compiler;

use Core\View\Exception\TemplateCompilerException;
use Support\{Num};
use Symfony\Component\Filesystem\Filesystem;
use function Support\path_valid;

/**
 * @internal
 */
final class ViewLoader
{
    private static Filesystem $filesystem;

    private bool $locked = false;

    /** @var array<array-key, string> */
    private array $viewDirectories = [];

    public function __construct(
        string|array $directories,
        bool         $lock = false,
    ) {
        $this->add( $directories );

        if ( $lock ) {
            $this->lock();
        }
    }

    public function get( string $view ) : string
    {
        // Sort and lock the Loader on first call
        if ( ! $this->locked ) {
            $this->lock();
        }

        // Return string views
        if ( ! \str_ends_with( $view, '.latte' ) ) {
            return $view;
        }

        if ( $this->validateFile( $view ) ) {
            return $view;
        }

        if ( \str_starts_with( $view, '@' ) ) {
            if ( ! \str_contains( $view, '/' ) ) {
                throw new TemplateCompilerException( 'Namespaced view calls must use the forward slash separator.' );
            }

            [$namespace, $view] = \explode( '/', $view, 2 );

            $path = $this->viewDirectories[$namespace] ?? throw new TemplateCompilerException( '' );

            return $this->normalizePath( $path, $view );
        }

        foreach ( $this->viewDirectories as $directory ) {
            $path = $this->normalizePath( $directory, $view );

            if ( $this->validateFile( $path ) ) {
                return $path;
            }
        }

        throw new TemplateCompilerException( 'Unable to load view: '.$view );
    }

    /**
     * Add one or more directories to the {@see ViewLoader} stack.
     *
     * - Non-numeric array keys will be treated as a namespace.
     * - Please ensure each path is a readable directory.
     *
     * @param array<array-key, string>|string $directories
     *
     * @return void
     */
    public function add( string|array $directories ) : void
    {
        foreach ( (array) $directories as $index => $directory ) {
            $this->addDirectory( $directory, $index );
        }
    }

    /**
     * Add a directory to the {@see ViewLoader} stack.
     *
     * - Please ensure each path is a readable directory.
     *
     * @param string   $path
     * @param null|int $priority
     *
     * @return void
     */
    public function addDirectory( string $path, ?int $priority = null ) : void
    {
        // TODO : Support passing a namespace for '@Namespace/template.view' matches

        if ( $this->locked ) {
            throw new TemplateCompilerException( 'Unable to add view; the '.$this::class.' is locked.' );
        }

        // TODO : Handle priority collision
        $priority ??= \count( $this->viewDirectories );

        $priority = Num::pad( $priority++, 0 );

        // Avoid duplicates, setting the latest $priority
        if ( $isset = \array_search( $path, $this->viewDirectories ) ) {
            unset( $this->viewDirectories[$isset] );
        }

        $this->viewDirectories[$priority] = $path;
    }

    public function lock( bool $sort = true ) : void
    {
        if ( $this->locked ) {
            return;
        }

        if ( $sort ) {
            \krsort( $this->viewDirectories, SORT_DESC );
        }

        $this->locked = true;
    }

    public function unlock() : void
    {
        $this->locked = false;
    }

    private function validateFile( string $path ) : bool
    {
        return \is_readable( $path ) && \is_file( $path );
    }

    protected function normalizePath( string ...$path ) : string
    {
        // Normalize separators
        $nroamlized = \str_replace( ['\\', '/'], DIRECTORY_SEPARATOR, $path );

        $isRelative = DIRECTORY_SEPARATOR === $nroamlized[0];

        // Implode->Explode for separator deduplication
        $exploded = \explode( DIRECTORY_SEPARATOR, \implode( DIRECTORY_SEPARATOR, $nroamlized ) );

        // Ensure each part does not start or end with illegal characters
        $exploded = \array_map( static fn( $item ) => \trim( $item, " \n\r\t\v\0\\/" ), $exploded );

        // Filter the exploded path, and implode using the directory separator
        $path = \implode( DIRECTORY_SEPARATOR, \array_filter( $exploded ) );

        // Preserve intended relative paths
        if ( $isRelative ) {
            $path = DIRECTORY_SEPARATOR.$path;
        }

        return $path;
    }

    public static function prepareDirectories( string|array $directories ) : array
    {
        $prepared = [];

        foreach ( (array) $directories as $index => $directory ) {
            if ( \is_string( $index ) && ! \is_numeric( $index ) ) {
                $key = \trim( $index );

                if ( \preg_match( '/^[a-zA-Z]+$/', $key ) ) {
                    throw new TemplateCompilerException( 'Invalid view namespace: '.$index );
                }
            }
            else {
                $key = (int) $index;
            }

            if ( ! path_valid( $directory, false, $exception ) ) {
                $message = 'Invalid view directory: '.$exception->getmessage();
                throw new TemplateCompilerException( $message, 500, $exception );
            }

            $prepared[$key] = $directory;
        }

        return $prepared;
    }

    private static function filesystem() : Filesystem
    {
        return self::$filesystem ??= new Filesystem();
    }
}
