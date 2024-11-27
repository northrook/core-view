<?php

namespace HTML;

final class Character
{
    public static function isPunctuation( string $string ) : bool
    {
        return \preg_match( '#^[.!]+$#', $string );
    }
}
