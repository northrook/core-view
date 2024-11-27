<?php

namespace HTML;

use Stringable;

final class Trim
{
    /**
     * @param null|string|Stringable $string
     *
     * @return string
     */
    public static function whitespace( string|Stringable|null $string ) : string
    {
        return \preg_replace( '#\s+#', ' ', \trim( (string) $string ) );
    }
}
