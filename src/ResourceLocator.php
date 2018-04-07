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
     * @var string The location base path
     */
    protected $basePath;


    protected $cache = [];

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
     * @param string $uri Input URI to be searched (can be a path ONLY)
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
     * Returns the canonicalized URI on success. The resulting path will have no '/./' or '/../' components.
     * Trailing delimiter `/` is kept.
     *
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
     * @param  string $uri
     * @return bool
     */
    /*public function isStream($uri)
    {
        try {
            list ($scheme,) = $this->normalize($uri, true, true);
        } catch (\Exception $e) {
            return false;
        }
        return $this->schemeExists($scheme);
    }*/

    /**
     * Find highest priority instance from a resource.
     * For example, if looking for a `test.json` ressource, only the top priority
     *  instance of `test.json` found will be returned.
     *
     * @param  string $uri Input URI to be searched (can be a file or path)
     * @throws \BadMethodCallException
     * @return string The ressource path
     */
    public function findResource($uri, $absolute = true, $first = false)
    {
        return $this->findCached($uri, false, $absolute, $first);
    }

    /**
     * Find all instances from a resource.
     * For example, if looking for a `test.json` ressource, all instance
     * of `test.json` found will be listed.
     *
     * @param string $uri Input URI to be searched (can be a file or path)
     * @return array An array of all the ressources path
     */
    public function findResources($uri, $absolute = true, $all = false)
    {
         return $this->findCached($uri, true, $absolute, $all);
    }

    protected function findCached($uri, $array, $absolute, $all)
    {
        if (!is_string($uri)) {
            throw new \BadMethodCallException('Invalid parameter $uri.');
        }

        // Local caching: make sure that the function gets only called at once for each file.
        $key = $uri .'@'. (int) $array . (int) $absolute . (int) $all;
        if (!isset($this->cache[$key])) {
            try {
                list ($scheme, $file) = $this->normalize($uri, true, true);
                if (!$file && $scheme === 'file') {
                    $file = $this->basePath;
                }
                $this->cache[$key] = $this->find($scheme, $file, $array, $absolute, $all);
            } catch (\BadMethodCallException $e) {
                $this->cache[$key] =  $array ? [] : false;
            }
        }
        return $this->cache[$key];
    }

    protected function buildLocationPaths(ResourcePath $path)
    {
        if ($path->isShared()) {
            // Path is shared. We return it's value
            return [$path->getPath()];
        }

        $list = [];
        foreach ($this->getLocations() as $location) {
            $list[] = trim($location->getPath(), '/') . '/' . $path->getPath();
        }

        return $list;
    }

    protected function find($scheme, $file, $array, $absolute, $all)
    {
        //echo "\nFINDING :: $scheme -- $file";
        if (!$this->pathExist($scheme)) {
            throw new \InvalidArgumentException("Invalid resource {$scheme}://");
        }
        $results = $array ? [] : false;
        $pathResource = $this->getPath($scheme);
        //echo "\nPATH RESOURCE :: " . print_r($pathResource, true);
        $paths = $this->buildLocationPaths($pathResource);
        //echo "\nPATHS :: " . print_r($paths, true);
        //foreach ($this->schemes[$scheme] as $prefix => $paths) {
            /*if ($prefix && strpos($file, $prefix) !== 0) {
                continue;
            }*/
            // Remove prefix from filename.
            $prefix = '';
            $filename = '/' . trim($file, '\/');
            foreach ($paths as $path) {
                if (is_array($path)) {
                    // Handle scheme lookup.
                    $relPath = trim($path[1] . $filename, '/');
                    $found = $this->find($path[0], $relPath, $array, $absolute, $all);
                    if ($found) {
                        if (!$array) {
                            return $found;
                        }
                        $results = array_merge($results, $found);
                    }
                } else {
                    // TODO: We could provide some extra information about the path to remove preg_match().
                    // Check absolute paths for both unix and windows
                    if (!$path || !preg_match('`^/|\w+:`', $path)) {
                        // Handle relative path lookup.
                        $relPath = trim($path . $filename, '/');
                        $fullPath = $this->basePath . '/' . $relPath;
                    } else {
                        // Handle absolute path lookup.
                        $fullPath = rtrim($path . $filename, '/');
                        if (!$absolute) {
                            throw new \RuntimeException("UniformResourceLocator: Absolute stream path with relative lookup not allowed ({$prefix})", 500);
                        }
                    }
                    //echo "\nFULLPATH :: $fullPath";
                    if ($all || file_exists($fullPath)) {
                        $current = $absolute ? $fullPath : $relPath;
                        if (!$array) {
                            return $current;
                        }
                        $results[] = $current;
                    }
                }
            }
        //}
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