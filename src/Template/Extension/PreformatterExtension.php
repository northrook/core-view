<?php

declare(strict_types=1);

namespace Core\View\Template\Extension;

use Latte;
use Latte\Compiler\{Node,
    Nodes\Html\ElementNode,
    Nodes\Php\ExpressionNode,
    Nodes\TextNode,
    NodeTraverser
};
use Latte\Compiler\Nodes\TemplateNode;
use Override;

final class PreformatterExtension extends Latte\Extension
{
    use NodeTraverserMethods;

    #[Override]
    public function getPasses() : array
    {
        return [
            'node-preformatter' => [$this, 'nodePreformatter'],
        ];
    }

    public function nodePreformatter( TemplateNode $template ) : void
    {
        dump( $template );
        $this->trimFragmentWhitespace( $template->main );

        ( new NodeTraverser() )->traverse(
            $template,
            // [$this, 'prepare'],
            leave : [$this, 'parse'],
        );
    }

    public function parse( Node $node ) : int|Node
    {
        // Skip expression nodes, as a component cannot exist there
        if ( $node instanceof ExpressionNode ) {
            return NodeTraverser::DontTraverseChildren;
        }

        // Components are only called from ElementNodes
        if ( ! $node instanceof ElementNode ) {
            return $node;
        }

        $this->elementAttributes( $node );

        if ( $node->content instanceof Latte\Compiler\Nodes\FragmentNode ) {
            $this->trimFragmentWhitespace( $node->content, $node->name );
        }
        // else {
        //     dump( $node );
        // }

        return $node;
    }

    protected function elementAttributes( ElementNode &$element ) : void
    {
        // Get a reference for the $element attributes
        $attributes = &$element->attributes->children;

        if ( ! $attributes ) {
            return;
        }

        $lastAttributeIndex = \count( $attributes ) - 1;

        foreach ( $attributes as $index => $attribute ) {
            // TODO : Parse attributes, find and warn for common errors
            //        like comma separating styles or classes

            if ( $attribute instanceof TextNode ) {
                // $attribute->content = \preg_replace( '#\s.+#', '', $attribute->content );
                $attribute->content = \trim( $attribute->content ).' ';
            }

            // Prevent trailing whitespace
            if ( $index === $lastAttributeIndex
                 && $attribute instanceof TextNode
            ) {
                unset( $attributes[$index] );
            }
        }
    }
}
