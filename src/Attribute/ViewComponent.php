<?php

declare(strict_types=1);

namespace Core\View\Attribute;

use Attribute;
use Northrook\HTML\Element\Tag;
use Northrook\Logger\Log;

/**
 * @used-by ComponentFactory, ComponentParser
 *
 * @author  Martin Nielsen
 */
#[Attribute( Attribute::TARGET_CLASS )]
final readonly class ViewComponent
{
    /** @var string[] */
    public array $tags;

    /**
     * Configure how this {@see ComponentInterface} is handled.
     *
     * ### Tag
     *
     * Assign one or more HTML tags to trigger this component.
     *
     * Use the `:` separator to indicate a component subtype,
     * which will call a method of the same name.
     *
     * ### Static
     * Components will by default be rendered at runtime,
     * but static components will render into the template cache as HTML.
     *
     * ### Priority
     * The higher the number, the earlier the Component is parsed.
     *
     * @param string[] $tag      [optional]
     * @param bool     $static   [false]
     * @param int      $priority [0]
     */
    public function __construct(
        string|array $tag = [],
        public bool  $static = false,
        public int   $priority = 0,
    ) {
        $this->setTags( (array) $tag );
    }

    private function setTags( array $tags ) : void
    {
        foreach ( $tags as $i => $tag ) {
            $tag = \strtolower( \trim( $tag ) );

            if ( ! \in_array( \strstr( $tag, ':', true ), Tag::TAGS, true ) ) {
                Log::warning( 'Unknown tag: '.$tag );
            }

            if ( ! \preg_match( '/^[a-zA-Z][a-zA-Z0-9._:-]*$/', $tag ) ) {
                Log::error( 'Tag {tag} contains invalid characters.', ['tag' => $tag] );
            }
        }

        $this->tags = \array_values( $tags );
    }
}
