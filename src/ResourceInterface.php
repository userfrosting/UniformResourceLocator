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
    public function getUri(): string;

    /**
     * Get the resource base path, aka the path that comes after the `://`.
     *
     * @return string
     */
    public function getBasePath(): string;

    /**
     * Extract the resource filename (test.txt -> test).
     *
     * @return string
     */
    public function getFilename(): string;

    /**
     * Extract the trailing name component (test.txt -> test.txt).
     *
     * @return string
     */
    public function getBasename(): string;

    /**
     * Extract the resource extension (test.txt -> txt).
     *
     * @return string
     */
    public function getExtension(): string;

    /**
     * @return ResourceLocationInterface|null
     */
    public function getLocation(): ?ResourceLocationInterface;

    /**
     * Magic function to convert the class into the resource absolute path.
     *
     * @return string The resource absolute path
     */
    public function __toString(): string;

    /**
     * @return string
     */
    public function getAbsolutePath(): string;

    /**
     * @return string
     */
    public function getPath(): string;

    /**
     * @return string
     */
    public function getLocatorBasePath(): string;

    /**
     * @param string $locatorBasePath
     *
     * @return static
     */
    public function setLocatorBasePath($locatorBasePath): self;

    /**
     * @return string
     */
    public function getSeparator(): string;

    /**
     * @param string $separator
     *
     * @return static
     */
    public function setSeparator($separator): self;

    /**
     * @return ResourceStreamInterface
     */
    public function getStream(): ResourceStreamInterface;
}
