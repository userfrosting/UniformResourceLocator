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
    public function getScheme(): string;

    /**
     * @param string $scheme
     *
     * @return static
     */
    public function setScheme($scheme): self;

    /**
     * @return string
     */
    public function getPath(): string;

    /**
     * @param string $path (default null)
     *
     * @return static
     */
    public function setPath($path): self;

    /**
     * @return string
     */
    public function getPrefix(): string;

    /**
     * @param string $prefix
     *
     * @return static
     */
    public function setPrefix($prefix): self;

    /**
     * @return bool
     */
    public function isShared(): bool;

    /**
     * @param bool $shared
     *
     * @return static
     */
    public function setShared($shared): self;
}
