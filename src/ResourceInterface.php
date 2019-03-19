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
 * Resource Interface.
 *
 * @author Louis Charette
 */
interface ResourceInterface
{
    /**
     * Get Resource URI.
     *
     * @return string
     */
    public function getUri();

    /**
     * Get the resource base path, aka the path that comes after the `://`.
     *
     * @return string
     */
    public function getBasePath();

    /**
     * Extract the resource filename (test.txt -> test).
     *
     * @return string
     */
    public function getFilename();

    /**
     * Extract the trailing name component (test.txt -> test.txt).
     *
     * @return string
     */
    public function getBasename();

    /**
     * Extract the resource extension (test.txt -> txt).
     *
     * @return string
     */
    public function getExtension();

    /**
     * @return ResourceLocationInterface
     */
    public function getLocation();

    /**
     * @return string
     */
    public function getAbsolutePath();

    /**
     * @return string
     */
    public function getPath();

    /**
     * @return string
     */
    public function getLocatorBasePath();

    /**
     * @param string $locatorBasePath
     *
     * @return static
     */
    public function setLocatorBasePath($locatorBasePath);

    /**
     * @return string
     */
    public function getSeparator();

    /**
     * @param string $separator
     *
     * @return static
     */
    public function setSeparator($separator);

    /**
     * @return ResourceStream
     */
    public function getStream();
}
