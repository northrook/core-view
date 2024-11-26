<?php

namespace Core\View\Component;

use Core\View\Template\Compiler\NodeCompiler;
use Core\View\Template\Node\ComponentNode;

interface NodeInterface
{
    /**
     * @param NodeCompiler $node
     *
     * @return ComponentNode
     */
    public function node( NodeCompiler $node ) : ComponentNode;

    /**
     * @internal
     *
     * @param NodeCompiler $node
     *
     * @return array
     */
    public static function nodeArguments( NodeCompiler $node ) : array;
}
