<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

use UserFrosting\UniformResourceLocator\ResourcePath;
use UserFrosting\UniformResourceLocator\ResourceLocation;
use UserFrosting\UniformResourceLocator\Exception\LocationNotFoundException;
use UserFrosting\UniformResourceLocator\Exception\PathNotFoundException;
use UserFrosting\UniformResourceLocator\Resources\ResourceInterface;

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
     * @var array Locale cache store of found resources
     */
    protected $cache = [];

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
        return array_reverse($this->locations);
    }

    /**
     * Return a list of all the locations registered
     *
     * @return array An array of registered name => location
     */
    public function listLocations()
    {
        return array_keys(array_reverse($this->locations));
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
     * Return a resource instance
     *
     * @param  string $uri
     * @return ResourceInterface
     */
    public function getResource($uri)
    {
        $path = $this->findResource($uri);

        //...!TODO
    }

    /**
     * List all ressources found at a given path.
     * Same as listing all file in a directory, except here all topmost
     * ressources will be returned when considering all locations
     *
     * @param string $uri Input URI to be searched (can be a uri/path ONLY)
     * @return array The ressources list
     */
    public function listResources($uri)
    {

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
     * Returns the canonicalized URI on success.
     * The resulting path will have no '/./' or '/../' components. Trailing delimiter `/` is kept.
     * Can also split the `scheme` for the `path` part of the uri if $splitStream parameter is set to true
     * By default (if $throwException parameter is not set to true) returns false on failure.
     *
     * @param string $uri
     * @param bool $throwException
     * @param bool $splitStream
     * @return string|array|bool
     * @throws \BadMethodCallException
     */
    public function normalize($uri, $throwException = false, $splitStream = false)
    {
        if (!is_string($uri)) {
            if ($throwException) {
                throw new \BadMethodCallException('Invalid parameter $uri.');
            } else {
                return false;
            }
        }
        $uri = preg_replace('|\\\|u', '/', $uri);
        $segments = explode('://', $uri, 2);
        $path = array_pop($segments);
        $scheme = array_pop($segments) ?: 'file';
        if ($path) {
            $path = preg_replace('|\\\|u', '/', $path);
            $parts = explode('/', $path);
            $list = [];
            foreach ($parts as $i => $part) {
                if ($part === '..') {
                    $part = array_pop($list);
                    if ($part === null || $part === '' || (!$list && strpos($part, ':'))) {
                        if ($throwException) {
                            throw new \BadMethodCallException('Invalid parameter $uri.');
                        } else {
                            return false;
                        }
                    }
                } elseif (($i && $part === '') || $part === '.') {
                    continue;
                } else {
                    $list[] = $part;
                }
            }
            if (($l = end($parts)) === '' || $l === '.' || $l === '..') {
                $list[] = '';
            }
            $path = implode('/', $list);
        }
        return $splitStream ? [$scheme, $path] : ($scheme !== 'file' ? "{$scheme}://{$path}" : $path);
    }

    /**
     * Returns true if uri is resolvable by using locator.
     *
     * @param  string $uri URI to test
     * @return bool True if is resolvable
     */
    public function isStream($uri)
    {
        try {
            list ($scheme,) = $this->normalize($uri, true, true);
        } catch (\Exception $e) {
            return false;
        }
        return $this->pathExist($scheme);
    }

    /**
     * Find highest priority instance from a resource. Return the path for said resource
     * For example, if looking for a `test.json` ressource, only the top priority
     * instance of `test.json` found will be returned.
     *
     * @param  string $uri Input URI to be searched (can be a file or directory)
     * @param  bool $absolute Whether to return absolute path.
     * @param  bool $first Whether to return first path even if it doesn't exist.
     * @throws \BadMethodCallException
     * @return string The ressource path
     */
    public function findResource($uri, $absolute = true, $first = false)
    {
        return $this->findCached($uri, false, $absolute, $first);
    }

    /**
     * Find all instances from a resource. Return an array of paths for said resource
     * For example, if looking for a `test.json` ressource, all instance
     * of `test.json` found will be listed.
     *
     * @param  string $uri Input URI to be searched (can be a file or directory)
     * @param  bool $absolute Whether to return absolute path.
     * @param  bool $all Whether to return all paths even if they don't exist.
     * @return array An array of all the ressources path
     */
    public function findResources($uri, $absolute = true, $all = false)
    {
        return $this->findCached($uri, true, $absolute, $all);
    }

    /**
     * Find a resource from the cached properties
     *
     * @param  string $uri Input URI to be searched (file or directory)
     * @param  bool $array Return an array or a single path
     * @param  bool $absolute Whether to return absolute path.
     * @param  bool $all Whether to return all paths even if they don't exist.
     * @return array|string The ressource path or an array of all the ressources path
     */
    protected function findCached($uri, $array, $absolute, $all)
    {
        // Validate arguments until php7 comes around
        if (!is_string($uri)) {
            throw new \BadMethodCallException('Invalid parameter $uri.');
        }

        // Local caching: make sure that the function gets only called at once for each file.
        // We create a key based on the submitted arguments
        $key = $uri .'@'. (int) $array . (int) $absolute . (int) $all;

        if (!isset($this->cache[$key])) {
            try {
                list ($scheme, $file) = $this->normalize($uri, true, true);
                if (!$file && $scheme === 'file') {
                    $file = $this->basePath;
                }
                $this->cache[$key] = $this->find($scheme, $file, $array, $absolute, $all);
            } catch (\BadMethodCallException $e) {
                // If something couldn't be found, return false or empty array
                $this->cache[$key] =  $array ? [] : false;
            }
        }
        return $this->cache[$key];
    }

    /**
     * Build the search path out of the defined scheme and locations.
     * If the scheme is shared, we don't need to involve locations and can return it's path directly
     *
     * @param  ResourcePath $path The scheme to search for
     * @return array The search paths based on this stream and all available locations
     */
    protected function searchPaths(ResourcePath $path)
    {
        // Path is shared. We return it's value
        if ($path->isShared()) {
            return [$path->getPath()];
        }

        $list = [];
        foreach ($this->getLocations() as $location) {
            $list[] = trim($location->getPath(), '/') . '/' . $path->getPath();
        }

        return $list;
    }

    /**
     * Returns path of a file (or directory) based on a search uri
     *
     * @param  string $scheme The scheme to search in
     * @param  string $file The file to search for
     * @param  bool $array Return an array or a single path
     * @param  bool $absolute Whether to return absolute path.
     * @param  bool $all Whether to return all paths even if they don't exist.
     * @throws \InvalidArgumentException
     * @return string|array Found
     */
    protected function find($scheme, $file, $array, $absolute, $all)
    {
        // Make sure path exist
        if (!$this->pathExist($scheme)) {
            throw new \InvalidArgumentException("Invalid resource {$scheme}://");
        }

        // Prepare result depending on $array parameter
        $results = $array ? [] : false;

        // Get path resource
        $pathResource = $this->getPath($scheme);

        // Get all search paths using all locations
        $paths = $this->searchPaths($pathResource);

        // Get filename
        $filename = '/' . trim($file, '\/');

        // Pass each search paths
        foreach ($paths as $path) {

            // Check if path from the ResourcePath is absolute or relative
            // for both unix and windows
            if (!preg_match('`^/|\w+:`', $path)) {
                // Handle relative path lookup.
                $relPath = trim($path . $filename, '/');
                $fullPath = $this->basePath . '/' . $relPath;
            } else {
                // Handle absolute path lookup.
                $fullPath = rtrim($path . $filename, '/');

                // If we have an absolute path and don't want an absolute
                // result, we are in a in dead end
                if (!$absolute) {
                    throw new \RuntimeException("Absolute stream path with relative lookup not allowed", 500);
                }
            }

            // Add the result to the list if the path exist, unless we want all results
            if ($all || file_exists($fullPath)) {
                $current = $absolute ? $fullPath : $relPath;
                if (!$array) {
                    return $current;
                }
                $results[] = $current;
            }
        }

        return $results;
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