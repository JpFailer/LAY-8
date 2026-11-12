<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (!function_exists('asset_url')) {
    /**
     * Devuelve la URL de un asset público con versión basada en filemtime
     * para habilitar caché del navegador e invalidar automáticamente al editar.
     */
    function asset_url(string $relativePath): string
    {
        $cleanPath = ltrim($relativePath, '/');
        $fullPath  = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
        $version   = is_file($fullPath) ? (string) filemtime($fullPath) : '1';

        return base_url($cleanPath) . '?v=' . $version;
    }
}
