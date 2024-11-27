<?php

declare(strict_types=1);

namespace HTML;

final class Character
{
    public static function isDelimiter( string $string ) : bool
    {
        return \preg_match( '#^[,;]+$#', $string );
    }

    public static function isPunctuation( string $string ) : bool
    {
        return \preg_match( '#^[.!]+$#', $string );
    }
}
