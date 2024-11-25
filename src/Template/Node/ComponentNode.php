<?php

namespace Core\View\Template\Node;

use Latte\Compiler\Nodes\{AreaNode};
use Latte\Compiler\PrintContext;
use Generator;

final class ComponentNode extends AreaNode
{
    public function __construct( public readonly string $content )
    {
    }

    private function echo() : string
    {
        $export = \var_export( $this->content, true );
        dump( $export );
        return 'echo '.$export.";\n";
    }

    public function print( PrintContext $context ) : string
    {
        return $this->content ? $this->echo() : '';
    }

    public function isWhitespace() : bool
    {
        return \trim( $this->content ) === '';
    }

    public function &getIterator() : Generator
    {
        false && yield;
    }
}
