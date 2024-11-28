<?php

declare(strict_types=1);

namespace Core\View\Component;

use Core\View\Template\Node\ComponentNode;
use Core\View\Template\NodeParser;

/**
 * @used-by ComponentNode trait
 */
interface NodeInterface
{
    /**
     * @param NodeParser $node
     *
     * @return ComponentNode
     */
    public function node( NodeParser $node ) : ComponentNode;
}
