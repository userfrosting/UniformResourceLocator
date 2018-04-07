<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

use UserFrosting\UniformResourceLocator\Exception\LocationNotFoundException;
use UserFrosting\UniformResourceLocator\Exception\PathNotFoundException;

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
    protected $paths = [];

    /**
     * @var array The list of registered paths
     */
    protected $locations = [];

    /**
     * @var string The location base path
     */
    protected $basePath;

    /**
     * Constructor
     *
     * @param string|null $basePath (default null)
     */
    public function __construct($basePath = '')
    {
        $this->setBasePath($basePath);
    }

    /**
     * Add an exisitng ResourcePath to the path list
     *
     * @param ResourcePath $path
     */
    public function addPath(ResourcePath $path)
    {
        $this->paths[$path->getScheme()] = $path;

        return $this;
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
        return $this;
    }

    /**
     * Unregister the specified path
     *
     * @param  string $scheme The path scheme
     * @return $this
     */
    public function removePath($scheme)
    {
        unset($this->paths[$scheme]);
        return $this;
    }

    /**
     * @param string $scheme The path scheme
     * @return ResourcePath
     * @throws PathNotFoundException If path is not registered
     */
    public function getPath($scheme)
    {
        if ($this->pathExist($scheme)) {
            return $this->paths[$scheme];
        } else {
            throw new PathNotFoundException;
        }
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
    public function listPaths()
    {
        return array_keys($this->paths);
    }

    /**
     * Returns true if a path has been defined
     *
     * @param  string $scheme The path scheme
     * @return bool
     */
    public function pathExist($scheme)
    {
        return isset($this->paths[$scheme]);
    }

    /**
     * Add an existing RessourceLocation instance to the location list
     *
     * @param ResourceLocation $location
     */
    public function addLocation(ResourceLocation $location)
    {
        $this->locations[$location->getName()] = $location;
        return $this;
    }

    /**
     * Register a new location
     *
     * @param  string $name The location name
     * @param  string $path The location base path (default null)
     * @return $this
     */
    public function registerLocation($name, $path = null)
    {
        $location = new ResourceLocation($name, $path);
        $this->addLocation($location);
        return $this;
    }

    /**
     * Unregister the specified location
     *
     * @param  string $name The location name
     * @return $this
     */
    public function removeLocation($name)
    {
        unset($this->locations[$name]);
        return $this;
    }

    /**
     * @param string $name The location name
     * @return ResourceLocation
     * @throws LocationNotFoundException If location is not registered
     */
    public function getLocation($name)
    {
        if ($this->locationExist($name)) {
            return $this->locations[$name];
        } else {
            throw new LocationNotFoundException;
        }
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
    public function listLocations()
    {
        return array_keys($this->locations);
    }

    /**
     * Returns true if a location has been defined
     *
     * @param  string $name The location name
     * @return bool
     */
    public function locationExist($name)
    {
        return isset($this->locations[$name]);
    }

    /**
     * Reset locator by removing all the registered paths and locations.
     *
     * @return $this
     */
    public function reset()
    {
        $this->paths = [];
        $this->locations = [];
        return $this;
    }

    /**
     * Find highest priority instance from a resource.
     * For example, if looking for a `test.json` ressource, only the top priority
     *  instance of `test.json` found will be returned.
     *
     * @param  string $uri Input URI to be searched (can be a file or path)
     * @throws \BadMethodCallException
     * @return string The ressource path
     */
    public function findResource($uri)
    {
        if (!is_string($uri)) {
            throw new \BadMethodCallException('Invalid parameter $uri.');
        }
    }

    /**
     * Find all instances from a resource.
     * For example, if looking for a `test.json` ressource, all instance
     * of `test.json` found will be listed.
     *
     * @param string $uri Input URI to be searched (can be a file or path)
     * @return array An array of all the ressources path
     */
    public function findResources($uri)
    {

    }

    /**
     * List all ressources found at a given path.
     * Same as listing all file in a directory, except here all topmost
     * ressources will be returned when considering all locations
     *
     * @param string $uri Input URI to be searched (can be a path ONLY)
     * @return array The ressources list
     */
    public function listResources($uri)
    {

    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param string|null $basePath
     *
     * @return static
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
        return $this;
    }
}