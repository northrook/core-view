<?php

declare(strict_types=1);

namespace Core\View\Template;

interface TemplateCompilerInterface
{
    /**
     * Renders a provided `view` to string.
     *
     * Accepts
     * - Path to template file, absolute or relative to provided `templateDirectory`
     * - String of `HTML`
     * - Template string
     *
     * @param string                      $view
     * @param array<string, mixed>|object $parameters [optional]
     *
     * @return string
     */
    public function render( string $view, object|array $parameters = [] ) : string;

    /**
     * Clears the entire template cache.
     *
     * ⚠️ This forces a recompilation of each template on-demand.
     *
     * @return bool
     */
    public function clearTemplateCache() : bool;

    /**
     * Removes unnecessary cached template files.
     *
     * Returns an array of pruned files.
     *
     * @return array<array-key, string>
     */
    public function pruneTemplateCache() : array;
}
