<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

use UserFrosting\UniformResourceLocator\ResourceLocation;

/**
 * Resource Class
 *
 * Contains information about a resource
 *
 * @author    Louis Charette
 */
class Resource
{
    /**
     * @var ResourceLocation $location
     */
    protected $location;

    /**
     * @var string $absolutePath Absolute path to the resource
     */
    protected $absolutePath;

    /**
     * @var string $relPath Relative path to the resource
     */
    protected $relPath;

    /**
     * @var ResourceStream $stream
     */
    protected $stream;

    /**
     * Constructor
     *
     * @param ResourceStream $stream Resource stream
     * @param ResourceLocation|null $location Resource location
     * @param string $absolutePath Resource absolute path
     * @param string $relPath Resource relative path
     */
    public function __construct(ResourceStream $stream, ResourceLocation $location = null, $absolutePath = '', $relPath = '')
    {
        $this->stream = $stream;
        $this->location = $location;
        $this->relPath = $relPath;
        $this->absolutePath = $absolutePath;
    }

    /**
     * Get Resource URI
     * @return string
     */
    public function getUri()
    {
        $stream = $this->stream;

        // Remove stream path from relative path
        $path = str_replace($stream->getPath(), '', $this->relPath);

        // Also remove location path
        if (!is_null($this->location)) {
            $locationPath = trim($this->location->getPath(), '/');
            $path = str_replace($locationPath, '', $path);
        }

        return $stream->getScheme() . '://' . trim($path, '/');
    }

    /**
     * @return ResourceLocation
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param ResourceLocation $location
     *
     * @return static
     */
    public function setLocation(ResourceLocation $location)
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return string
     */
    public function getAbsolutePath()
    {
        return $this->absolutePath;
    }

    /**
     * Magic function to convert the class into the resource absolute path
     *
     * @return string The resource absolute path
     */
    public function __toString()
    {
        return $this->getAbsolutePath();
    }

    /**
     * @param string $absolutePath
     *
     * @return static
     */
    public function setAbsolutePath($absolutePath)
    {
        $this->absolutePath = $absolutePath;
        return $this;
    }

    /**
     * @return string
     */
    public function getRelPath()
    {
        return $this->relPath;
    }

    /**
     * @param string $relPath
     *
     * @return static
     */
    public function setRelPath($relPath)
    {
        $this->relPath = $relPath;
        return $this;
    }

    /**
     * @return ResourceStream
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @param ResourceStream $stream
     *
     * @return static
     */
    public function setStream(ResourceStream $stream)
    {
        $this->stream = $stream;
        return $this;
    }
}