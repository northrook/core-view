<?php

declare(strict_types=1);

namespace Core\View\Component;

use Stringable;

/**
 * @phpstan-require-implements ContentInterface
 */
trait InnerContent
{
    public readonly Content $content;

    final protected function setContent( string|Stringable ...$content ) : void
    {
        $this->content = new Content( ...$content );
    }
}
