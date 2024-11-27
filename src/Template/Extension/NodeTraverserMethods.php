<?php

declare(strict_types=1);

namespace Core\View\Template\Extension;

use Latte\Compiler\{Node, Nodes\FragmentNode, Nodes\TextNode, NodeTraverser};
use Latte\Compiler\Nodes\Html\ElementNode;
use Latte\Compiler\Nodes\Php\ExpressionNode;
use HTML\{Character, Tag, Trim};

trait NodeTraverserMethods
{
    /**
     * Loop one level of children and trim whitespace from the first and last child.
     *
     * @param FragmentNode $fragment
     * @param ?string      $nodeTag
     *
     * @return void
     */
    final protected function trimFragmentWhitespace( FragmentNode &$fragment, ?string $nodeTag = null ) : void
    {
        // Only parse if the node has children
        if ( ! $fragment->children ) {
            return;
        }

        $lastIndex = \count( $fragment->children ) - 1;

        /** @var int $index */
        foreach ( $fragment->children as $index => $node ) {
            // We're only trimming TextNodes
            if ( ! $node instanceof TextNode ) {
                continue;
            }

            $firstNode = 0      === $index;
            $lastNode  = $index === $lastIndex;

            $before    = $fragment->children[$index - 2] ?? null;
            $previous  = $fragment->children[$index - 1] ?? null;
            $next      = $fragment->children[$index + 1] ?? null;
            $after     = $fragment->children[$index + 2] ?? null;
            $linebreak = \str_contains( PHP_EOL, $node->content );

            if ( $node->isWhitespace() && $this->pruneWhitespace( $node, ( $firstNode || $lastNode ) ) ) {
                unset( $fragment->children[$index] );

                continue;
            }

            if ( $nodeTag ) {
                $this->balanceContentWhitespace(
                    $node,
                    $nodeTag,
                    $previous instanceof ElementNode ? $previous->name : null,
                    $next instanceof ElementNode ? $next->name : null,
                    $before,
                    $after,
                );
            }
        }
        // dump( $fragment->children );
    }

    /**
     * Optimize whitespace.
     *
     * @param TextNode   &$textNode
     * @param ?string    $nodeTag
     * @param ?string    $previousTag
     * @param ?string    $nextTag
     * @param null|mixed $before
     * @param null|mixed $after
     *
     * @return void
     */
    final protected function balanceContentWhitespace(
        TextNode & $textNode,
        ?string  $nodeTag = null,
        ?string  $previousTag = null,
        ?string  $nextTag = null,
        mixed    $before = null,
        mixed    $after = null,
    ) : void {
        // if ( Tag::isContent( $nodeTag ) ) {
        // }
        $textNode->content = Trim::whitespace( $textNode->content );

        if ( ! ( $previousTag || $nextTag ) || ! $textNode->content ) {
            return;
        }

        if ( $nextTag ) {
            $textNode->content = "{$textNode->content} ";
        }

        if ( $previousTag && ! Character::isPunctuation( $textNode->content ) ) {
            // dump( "[{$previousTag}]{$textNode->content}" );
            $textNode->content = " {$textNode->content}";
        }

        // dump( "[{$nodeTag}]" );

        // dump( $textNode->content );

        // dump(
        //     [
        //         'this'   => $nodeTag,
        //         'text'   => $textNode->content,
        //         'prev'   => $previousTag,
        //         'next'   => $nextTag,
        //         'before' => $before,
        //         'after'  => $after,
        //     ],
        // );
    }

    private function pruneWhitespace( TextNode &$textNode, bool $edgeNode ) : bool
    {
        $linebreak = \str_contains( PHP_EOL, $textNode->content );

        $textNode->content = \trim( $textNode->content );

        if ( $linebreak ) {
            $textNode->content .= \PHP_EOL;
        }

        return (bool) ( $edgeNode || ! $textNode->content );
    }

    /**
     * @param Node $node
     *
     * @phpstan-assert-if-true ElementNode $node
     * @return false|int|Node
     */
    final protected function skip( Node $node ) : int|Node|false
    {
        // Skip expression nodes, as a component cannot exist there
        if ( $node instanceof ExpressionNode ) {
            return NodeTraverser::DontTraverseChildren;
        }

        // Components are only called from ElementNodes
        if ( ! $node instanceof ElementNode ) {
            return $node;
        }

        return false;
    }
}
