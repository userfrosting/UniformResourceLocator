<?php

/*
 * UserFrosting Uniform Resource Locator (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @copyright Copyright (c) 2013-2019 Alexander Weissman, Louis Charette
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

class Normalizer
{
    /**
     * Returns the canonicalized URI on success.
     * The resulting path will have no '/./' or '/../' components. Trailing delimiter `/` is kept.
     * Can also split the `scheme` for the `path` part of the uri if $splitStream parameter is set to true
     * By default (if $throwException parameter is not set to true) returns false on failure.
     *
     * @param string $uri
     * @param bool   $throwException
     * @param bool   $splitStream
     *
     * @throws \BadMethodCallException
     *
     * @return string|array|bool Return false if path is invalid
     */
    public static function normalize($uri, $throwException = false, $splitStream = false)
    {
        if (!is_string($uri)) {
            if ($throwException) {
                throw new \BadMethodCallException("Invalid parameter $uri.");
            } else {
                return false;
            }
        }

        $separator = '/';

        $uri = preg_replace('|\\\|u', $separator, $uri);
        $segments = explode('://', $uri, 2);
        $path = array_pop($segments);
        $scheme = array_pop($segments) ?: '';
        if ($path) {
            $path = preg_replace('|\\\|u', $separator, $path);
            $parts = explode($separator, $path);
            $list = [];
            foreach ($parts as $i => $part) {
                if ($part === '..') {
                    $part = array_pop($list);
                    if ($part === null || $part === '' || (!$list && strpos($part, ':'))) {
                        if ($throwException) {
                            throw new \BadMethodCallException('Invalid parameter $uri.');
                        } else {
                            return false;
                        }
                    }
                } elseif (($i && $part === '') || $part === '.') {
                    continue;
                } else {
                    $list[] = $part;
                }
            }
            if (($l = end($parts)) === '' || $l === '.' || $l === '..') {
                $list[] = '';
            }
            $path = implode($separator, $list);
        }

        return $splitStream ? [$scheme, $path] : ($scheme !== '' ? "{$scheme}://{$path}" : $path);
    }

    /**
     * Normalise a path:
     *  - Make sure all `\` (from a Windows path) are changed to `/`
     *  - Make sure a trailling slash is present
     *  - Doesn't change the beginning of the path (don't change absolute / relative path), but will change `C:\` to `C:/`
     *
     * @param string $path
     *
     * @throws \BadMethodCallException
     *
     * @return string|false Return false if path is invalid
     */
    public static function normalizePath(string $path)
    {
        $path = self::normalize($path);

        // Before adding back `/`, make sure it's not empty again
        if ($path !== '') {
            $path = rtrim($path, '/').'/';
        }

        return $path;
    }
}
