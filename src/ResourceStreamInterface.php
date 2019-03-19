<?php

/*
 * UserFrosting Uniform Resource Locator (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @copyright Copyright (c) 2013-2019 Alexander Weissman, Louis Charette
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

/**
 * ResourceStreamInterface Interface.
 *
 * @author    Louis Charette
 */
interface ResourceStreamInterface
{
    /**
     * @return string
     */
    public function getScheme();

    /**
     * @param string $scheme
     *
     * @return static
     */
    public function setScheme($scheme);

    /**
     * @return string
     */
    public function getPath();

    /**
     * @param string $path (default null)
     *
     * @return static
     */
    public function setPath($path);

    /**
     * @return string
     */
    public function getPrefix();

    /**
     * @param string $prefix
     *
     * @return static
     */
    public function setPrefix($prefix);

    /**
     * @return bool
     */
    public function isShared();

    /**
     * @param bool $shared
     *
     * @return static
     */
    public function setShared($shared);
}
