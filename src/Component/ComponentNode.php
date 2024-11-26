<?php

namespace Core\View\Component;

use Core\View\Template\Compiler\NodeCompiler;

/**
 * @phpstan-require-implements NodeInterface
 */
trait ComponentNode
{
    public static function nodeArguments( NodeCompiler $node ) : array
    {
        return [
            'tag'        => $node->tag,
            'attributes' => $node->attributes(),
            'content'    => $node->parseContent(),
        ];
    }
}
