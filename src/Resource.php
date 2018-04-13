<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

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

    //protected $stream;

    //protected $uri;

    public function __construct($absolutePath = '', $relPath = '', $location = null)
    {
        $this->relPath = $relPath;
        $this->absolutePath = $absolutePath;
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
}