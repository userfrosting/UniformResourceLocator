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
 * Resource Class.
 *
 * Resources are used to represent a file with info regarding the stream and
 * Location used to find it. When a resource is created, we save the stream used
 * to find it, the location where it was found, and the absolute and relative
 * paths of the file. Using this information, we can later rebuilt the URI used
 * to find this file. Since the full path will contains the relative location of
 * the stream and location inside the filesystem, this information will be
 * removed to recrete the relative 'basepath' of the file, allowing the
 * recreatation of the uri (scheme://basePath).
 *
 * @author Louis Charette
 */
class Resource implements ResourceInterface
{
    /**
     * @var ResourceLocationInterface|null
     */
    protected $location;

    /**
     * @var string Relative path to the resource, above the locator base path
     */
    protected $path;

    /**
     * @var string Relative path to the resource, above the locator base path
     */
    protected $locatorBasePath;

    /**
     * @var ResourceStreamInterface
     */
    protected $stream;

    /**
     * Constructor.
     *
     * @param ResourceStreamInterface        $stream          ResourceStream used to locate this resource
     * @param ResourceLocationInterface|null $location        ResourceLocation used to locate this resource
     * @param string                         $path            Resource path, relative to the locator base path, and containing the stream and location path
     * @param string                         $locatorBasePath Locator base Path (default to '')
     */
    public function __construct(ResourceStreamInterface $stream, ResourceLocationInterface $location = null, string $path, string $locatorBasePath = '')
    {
        $this->setStream($stream);
        $this->setLocation($location);
        $this->setPath($path);
        $this->setLocatorBasePath($locatorBasePath);
    }

    /**
     * Get Resource URI
     * Also adds the prefix stream prefix if it existprefix.
     *
     * @return string
     */
    public function getUri(): string
    {
        // Using parts so the separator is added only if both parts are not empty
        $parts = [];

        // Adds the stream prefix
        if ($this->stream->getPrefix() != '') {
            $parts[] = $this->stream->getPrefix();
        }

        // Add resource base path if not empty
        if ($this->getBasePath() != '') {
            $parts[] = $this->getBasePath();
        }

        // Glue parts togeter.
        $path = implode('/', $parts);

        return $this->stream->getScheme().'://'.$path;
    }

    /**
     * Get the resource base path, aka the path that comes after the `://`.
     *
     * To to this, we use the relative path and remove
     * the stream and location base path. For example, a stream with a base path
     * of `data/foo/`, will return a relative path for every resource it find as
     * `data/foo/filename.txt`. So we want to remove the `data/foo/` part to
     * keep only the `filename.txt` part, aka the part after the `://` in the URI.
     *
     * Same goes for the location part, which comes before the stream:
     * `locations/locationA/data/foo`
     *
     * @return string
     */
    public function getBasePath(): string
    {
        // Start with the stream relative path as a search path.
        $searchPattern = preg_replace('#^'.preg_quote($this->getLocatorBasePath()).'#', '', $this->stream->getPath());

        // Add the location path to the search path if there's a location
        if (!is_null($this->getLocation())) {

            // We'll also need to remove the locator base path from the locator path
            // as it won't be removed by the previous attempt
            $locatorPath = preg_replace('#^'.preg_quote($this->getLocatorBasePath()).'#', '', $this->getLocation()->getPath());
            $searchPattern = Normalizer::normalize($locatorPath.'/'.$searchPattern);
        }

        // Remove the search path from the beginning of the resource path
        // then trim any beginning slashes from the resulting path
        $result = preg_replace('#^'.preg_quote($searchPattern).'#', '', $this->getPath());
        $result = ltrim($result, '/');
        $result = ltrim($result, '\\');

        return $result;
    }

    /**
     * Extract the resource filename (test.txt -> test).
     *
     * @return string
     */
    public function getFilename(): string
    {
        return pathinfo($this->getPath(), PATHINFO_FILENAME);
    }

    /**
     * Extract the trailing name component (test.txt -> test.txt).
     *
     * @return string
     */
    public function getBasename(): string
    {
        return pathinfo($this->getPath(), PATHINFO_BASENAME);
    }

    /**
     * Extract the resource extension (test.txt -> txt).
     *
     * @return string
     */
    public function getExtension(): string
    {
        return pathinfo($this->getPath(), PATHINFO_EXTENSION);
    }

    /**
     * @return ResourceLocationInterface|null
     */
    public function getLocation(): ?ResourceLocationInterface
    {
        return $this->location;
    }

    /**
     * Set the value of location
     *
     * @param ResourceLocationInterface|null $location
     *
     * @return self
     */
    public function setLocation(?ResourceLocationInterface $location): self
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return string
     */
    public function getAbsolutePath(): string
    {
        return $this->getLocatorBasePath().$this->getPath();
    }

    /**
     * Magic function to convert the class into the resource absolute path.
     *
     * @return string The resource absolute path
     */
    public function __toString(): string
    {
        return $this->getAbsolutePath();
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set relative path to the resource, above the locator base path
     *
     * @param string $path Relative path to the resource, above the locator base path
     *
     * @return self
     */
    public function setPath(string $path): self
    {
        $this->path = Normalizer::normalize($path);

        return $this;
    }

    /**
     * @return string
     */
    public function getLocatorBasePath(): string
    {
        return $this->locatorBasePath;
    }

    /**
     * @param string $locatorBasePath
     *
     * @return static
     */
    public function setLocatorBasePath(string $locatorBasePath): ResourceInterface
    {
        $this->locatorBasePath = $locatorBasePath;

        return $this;
    }

    /**
     * @return ResourceStreamInterface
     */
    public function getStream(): ResourceStreamInterface
    {
        return $this->stream;
    }

    /**
     * Set the value of stream
     *
     * @param ResourceStreamInterface $stream
     *
     * @return self
     */
    public function setStream(ResourceStreamInterface $stream): self
    {
        $this->stream = $stream;

        return $this;
    }
}
