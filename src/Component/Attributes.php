<?php

declare(strict_types=1);

namespace Core\View\Component;

use Stringable;

/**
 * @internal
 */
final class Attributes implements Stringable
{
    public function __construct(
        array $attributes = [],
    ) {
    }

    public function __toString()
    {
        return ' data-attr="component-attributes"';
    }
}
