<?php

declare(strict_types=1);

namespace Core\View\Interface;

/**
 * @uses IconInterface, IconPackInterface
 */
interface IconServiceInterface
{
    /**
     * Retrieve an {@see IconInterface} from the current {@see IconPackInterface}.
     *
     * @param string  $name
     * @param array   $attributes
     * @param ?string $fallback
     *
     * @return ?IconInterface `null` on errors or not found
     */
    public function getIcon( string $name, array $attributes = [], ?string $fallback = null ) : ?IconInterface;

    /**
     * Retrieve a registered {@see IconPackInterface}, or the current default if no `$name` is provided.
     *
     * @param ?string $name
     *
     * @return IconPackInterface
     */
    public function getIconPack( ?string $name = null ) : IconPackInterface;

    /**
     * Check if an icon is present in a given pack.
     *
     * @param string      $name
     * @param null|string $pack
     *
     * @return bool
     */
    public function hasIcon( string $name, ?string $pack = null ) : bool;

    /**
     * Check if the {@see IconServiceInterface} is aware of a given {@see IconPackInterface}.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasIconPack( string $name ) : bool;
}
