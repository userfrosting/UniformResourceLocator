<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

/**
 * ResourceLocator Class
 *
 * The locator is used to find resources.
 *
 * @author    Louis Charette
 */
class ResourceLocator
{
    /**
     * @var array The list of registered paths
     */
    protected $paths;

    /**
     * @var array The list of registered paths
     */
    protected $locations;

    /**
     * @var string The location base path
     */
    protected $basePath;

    public function __construct($basePath = '')
    {
        $this->setBasePath($basePath);
    }

    public function addPath(ResourcePath $path)
    {
        $this->paths[] = $path;
    }

    public function registerPath($scheme, $path, $shared = false)
    {

    }

    public function addLocation(ResourceLocation $location)
    {
        $this->locations[] = $location;
    }

    public function registerLocation($name, $path)
    {

    }

    public function findResource()
    {

    }

    public function findResources()
    {

    }

    public function listResources()
    {

    }

    protected function setupStreamWrapper()
    {

    }

    protected function unsetStreamWrapper()
    {

    }

    /**
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * @return array
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param string $basePath
     *
     * @return static
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
        return $this;
    }
}