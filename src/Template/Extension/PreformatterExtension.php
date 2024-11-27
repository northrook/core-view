<?php

declare(strict_types=1);

namespace Core\View\Template\Extension;

use Latte;
use Latte\Compiler\{Node, NodeHelpers, Nodes\Html\ElementNode, Nodes\Php\ExpressionNode, Nodes\TextNode, NodeTraverser};
use Latte\Compiler\Nodes\TemplateNode;
use Northrook\HTML\Element\Tag;
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

        $this->trimFragmentWhitespace( $node->content, $node->name );

        return $node;
    }

    protected function elementContent( ElementNode &$element ) : void
    {
        /** @var Latte\Compiler\Nodes\FragmentNode $content */
        $content = &$element->content;

        if ( ! $content ) {
            return;
        }

        $lastContentIndex = \count( $content->children ) - 1;

        if ( $lastContentIndex < 0 ) {
            return;
        }

        $inline = [...Tag::HEADING, ...Tag::INLINE, 'p'];

        /** @var int $index */
        foreach ( $content->children as $index => $node ) {
            if ( ! $node instanceof TextNode ) {
                continue;
            }

            $first = 0      === $index;
            $last  = $index === $lastContentIndex;

            if ( \in_array( $element->name, $inline, true ) ) {
                $node->content = \trim( \preg_replace( '#\s+#', ' ', $node->content ) );
                if ( $index !== $lastContentIndex ) {
                    $node->content = "{$node->content} ";
                }
                $node->content = $first ? \ltrim( $node->content ) : \rtrim( $node->content );
                // dump( $node->content );

                if ( ! $node->content ) {
                    unset( $content->children[$index] );

                    continue;
                }

                if ( isset( $content->children[$index + 1] ) && $content->children[$index + 1] instanceof ElementNode ) {
                    // dump( $content->children[$index + 1] );
                    $node->content = \rtrim( $node->content ).' ';
                    // dump( $content->children[$index + 1] );
                }
            }
        }
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
