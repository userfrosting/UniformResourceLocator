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
 * [Description]
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
     * @param string $absolutePath Resource absolute path
     * @param string $relPath Resource relative path
     * @param ResourceStream|null $stream Resource stream
     * @param ResourceLocation|null $location Resource location
     */
    public function __construct($absolutePath = '', $relPath = '', ResourceStream $stream = null, ResourceLocation $location = null)
    {
        $this->relPath = $relPath;
        $this->absolutePath = $absolutePath;
        $this->stream = $stream;
        $this->location = $location;
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