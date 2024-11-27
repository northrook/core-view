<?php

declare(strict_types=1);

namespace Core\View;

// ? View : Could be a template file, raw HTML, or text string
// Rendering a View will _not_ pass any templates through the ComponentFactory

// ? Template : Always a template
// ComponentFactory passes

// ? Content : Could be raw HTML, or a text string
// ? HTML : HTML expected
// ? Text : Always a text string

use Core\View\Render\HtmlContent;
use Core\View\Template\TemplateCompiler;
use Interface\Singleton;
use Northrook\Logger\Log;
use Symfony\Contracts\Service\Attribute\Required;

final class Render implements Singleton
{
    private static TemplateCompiler $staticTemplateCompiler;

    private static ?Render $instance = null;

    private readonly ?TemplateCompiler $compiler;

    private function __construct()
    {
    }

    #[Required]
    public function setStaticCompiler( ?TemplateCompiler $compiler ) : void
    {
        $this::$instance           ??= $this;
        $this::$instance->compiler ??= $compiler;
    }

    public static function view(
        string            $view,
        array             $data = [],
        bool              $cache = false,
        ?TemplateCompiler $compiler = null,
    ) : string {
        // Parse template ?: html

        return $view;
    }

    public static function template(
        string            $view,
        array             $data = [],
        bool              $cache = false,
        ?TemplateCompiler $compiler = null,
    ) : string {
        if ( $cache ) {
            $compiler = isset( Render::$instance ) ? Render::$instance->compiler : null;

            if ( ! $compiler ) {
                Log::error(
                    'Unable to cache {view}, no static compiler has been set.',
                    ['view' => $view],
                );
                $cache = false;
            }
        }

        $compiler ??= Render::$staticTemplateCompiler ??= new TemplateCompiler();

        return $compiler->render( $view, $data, cache : $cache );
    }

    public static function content( ...$content ) : string
    {
        $render = new HtmlContent( ...$content );

        $render->parse();

        return $render->render();
    }
}
