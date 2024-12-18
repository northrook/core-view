<?php

declare(strict_types=1);

namespace Core\View\Template\Extension;

use Closure;
use Core\View\Interface\{IconInterface, IconPackInterface, IconServiceInterface};
use Latte\Extension;

final class IconPackExtension extends Extension
{
    /**
     * @param Closure(): IconServiceInterface $lazyIconService
     */
    public function __construct( private readonly Closure $lazyIconService )
    {
    }

    public function getFunctions() : array
    {
        return [
            'icon'     => [$this, 'getIcon'],
            'iconPack' => [$this, 'getIconPack'],
        ];
    }

    public function getIcon( string $name, string|array ...$attributes ) : ?IconInterface
    {
        return $this->getIconService()->getIcon( $name, $attributes );
    }

    public function getIconPack( ?string $name ) : IconPackInterface
    {
        return $this->getIconService()->getIconPack( $name );
    }

    private function getIconService() : IconServiceInterface
    {
        return ( $this->lazyIconService )();
    }
}
