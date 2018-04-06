<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

use Illuminate\Support\Collection;

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

    /**
     * Constructor
     *
     * @param string $basePath (default '')
     */
    public function __construct($basePath = '')
    {
        $this->setBasePath($basePath);
    }

    /**
     * Add a new ResourcePath to the path list
     *
     * @param ResourcePath $path
     */
    public function addPath(ResourcePath $path)
    {
        $this->paths[] = $path;

        // Register the path as a stream wrapper
        $this->setupStreamWrapper($path);
    }

    /**
     * Register a new path
     *
     * @param  string  $scheme
     * @param  string  $path (default null)
     * @param  bool    $shared (default false)
     * @return void
     */
    public function registerPath($scheme, $path = null, $shared = false)
    {
        $path = new ResourcePath($scheme, $path, $shared);
        $this->addPath($path);
    }

    public function addLocation(ResourceLocation $location)
    {
        $this->locations[] = $location;
    }

    public function registerLocation($name, $path = null)
    {
        $location = new ResourceLocation($name, $path);
        $this->addLocation($location);
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

    protected function setupStreamWrapper(ResourcePath $path)
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
     * Return a list of all the path scheme registered
     * @return array An array of registered scheme => location
     */
    public function getPathsList()
    {
        $paths = new Collection($this->getPaths());
        $paths = $paths->mapWithKeys(function ($path, $key) {
            return [$path->getScheme() => $path->getPath()];
        });
        return $paths->all();
    }

    /**
     * @return array
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * Return a list of all the locations registered
     *
     * @return array An array of registered name => location
     */
    public function getLocationsList()
    {
        $locations = new Collection($this->getLocations());
        $locations = $locations->mapWithKeys(function ($location) {
            return [$location->getName() => $location->getPath()];
        });
        return $locations->all();
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