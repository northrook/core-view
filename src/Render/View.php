<?php

declare(strict_types=1);

namespace Core\View\Render;

use Latte\Runtime\HtmlStringable;

/**
 * @phpstan-param string $html
 */
abstract class View implements HtmlStringable
{
    private string $html;

    public function __construct( string $html )
    {
        $this->html = $html;
    }

    final public function __toString() : string
    {
        return $this->html;
    }
}
