<?php

namespace Core\View\Template\Node;

use Latte\Compiler\Nodes\{AreaNode};
use Core\View\Template\Compiler\{NodeCompiler, NodeExporter};
use Latte\Compiler\PrintContext;
use Generator;
use const Cache\AUTO;

final class ComponentNode extends AreaNode
{
    public readonly string $name;

    public readonly string $arguments;

    public readonly ?int $cache;

    /**
     * @param string $name
     *
     * @param array{tag: string, attributes: array<string, ?string>, content: array<array-key, string>}|NodeCompiler $arguments
     * @param ?int                                                                                                   $cache     [AUTO]
     */
    public function __construct(
        string             $name,
        array|NodeCompiler $arguments = [],
        ?int               $cache = AUTO,
    ) {
        if ( $arguments instanceof NodeCompiler ) {
            $arguments = ComponentNode::nodeArguments( $arguments );
            \assert( \is_array( $arguments ) );
        }

        $export          = new NodeExporter();
        $this->name      = $export->string( $name );
        $this->arguments = $export->arguments( $arguments );
        $this->cache     = $export->cacheConstant( $cache );
    }

    public function print( PrintContext $context ) : string
    {
        return <<<VIEW
            echo \$this->global->component->render(
                component : {$this->name},
                arguments : {$this->arguments},
                cache     : {$this->cache},
            );
            VIEW;
    }

    public function &getIterator() : Generator
    {
        false && yield;
    }

    public static function nodeArguments( NodeCompiler $node ) : array
    {
        return [
            'tag'        => $node->tag,
            'attributes' => $node->attributes(),
            'content'    => $node->parseContent(),
        ];
    }
}
