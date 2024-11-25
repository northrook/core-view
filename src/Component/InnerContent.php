<?php

namespace Core\View\Component;

use Stringable;

trait InnerContent
{
    public readonly Content $content;

    final protected function setContent( string|Stringable ...$content ) : void
    {
        $this->content = new Content( ...$content );
    }
}
