<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

use Illuminate\Filesystem\Filesystem;
use UserFrosting\UniformResourceLocator\Resource;
use UserFrosting\UniformResourceLocator\ResourceStream;
use UserFrosting\UniformResourceLocator\ResourceLocation;
use UserFrosting\UniformResourceLocator\Exception\LocationNotFoundException;
use UserFrosting\UniformResourceLocator\Exception\StreamNotFoundException;
use RocketTheme\Toolbox\ResourceLocator\ResourceLocatorInterface;
use RocketTheme\Toolbox\StreamWrapper\StreamBuilder;
use RocketTheme\Toolbox\StreamWrapper\Stream;

/**
 * ResourceLocator Class
 *
 * The locator is used to find resources.
 *
 * @author    Louis Charette
 */
class ResourceLocator implements ResourceLocatorInterface
{
    /**
     * @var array The list of registered streams
     */
    protected $streams = [];

    /**
     * @var array The list of registered locations
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
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var StreamBuilder
     */
    protected $streamBuilder;

    /**
     * Constructor
     *
     * @param string|null $basePath (default null)
     */
    public function __construct($basePath = '')
    {
        // Set base path
        $this->setBasePath($basePath);

        // Get a Filesystem instance
        $this->filesystem = new Filesystem;

        // Setup stream
        Stream::setLocator($this);

        // Get the builder
        $this->streamBuilder = new StreamBuilder();
    }

    /**
     * @param  string $uri
     * @return string|bool
     * @throws \BadMethodCallException
     */
    public function __invoke($uri)
    {
        if (!is_string($uri)) {
            throw new \BadMethodCallException('Invalid parameter $uri.');
        }
        return $this->findResource($uri, true);
    }

    /**
     * Add an exisitng ResourceStream to the stream list
     *
     * @param ResourceStream $stream
     */
    public function addStream(ResourceStream $stream)
    {
        $this->streams[$stream->getScheme()][$stream->getPrefix()][] = $stream;
        $this->setupStreamWrapper($stream->getScheme());

        // Sort in reverse order to get longer prefixes to be matched first.
        krsort($this->streams[$stream->getScheme()]);

        return $this;
    }

    /**
     * Register the scheme as a php stream wrapper
     *
     * @param  string $scheme The stream scheme
     */
    protected function setupStreamWrapper($scheme)
    {
        // Make sure stream does not already exist
        if ($this->streamBuilder->isStream($scheme)) {
            return;
        }

        // First unset the sheme. Prevent issue if someone else already registered it
        $this->unsetStreamWrapper($scheme);

        // register the scheme as a stream wrapper
        $this->streamBuilder->add($scheme, Stream::class);
    }

    /**
     * Unset a php stream wrapper
     *
     * @param string $scheme The stream scheme
     */
    protected function unsetStreamWrapper($scheme)
    {
        $this->streamBuilder->remove($scheme);

        if (in_array($scheme, stream_get_wrappers())) {
            stream_wrapper_unregister($scheme);
        }
    }

    /**
     * Register a new stream
     *
     * @param  string  $scheme
     * @param  string  $prefix (default '')
     * @param  string|array|null  $paths (default null). When using null path, the scheme will be used as a path
     * @param  bool    $shared (default false) Shared resoureces are not affected by locations
     * @return void
     */
    public function registerStream($scheme, $prefix = '', $paths = null, $shared = false)
    {
        if (is_null($paths)) {
            $stream = new ResourceStream($scheme, $prefix, null, $shared);
            $this->addStream($stream);
        } else {

            // Invert arrays list. Last path has priority
            $paths = array_reverse((array) $paths);
            foreach($paths as $path) {
                $stream = new ResourceStream($scheme, $prefix, $path, $shared);
                $this->addStream($stream);
            }
        }

        return $this;
    }

    /**
     * AddPath function. Used to preserve compatibility with RocketTheme/Toolbox
     *
     * @param string  $scheme
     * @param string  $prefix
     * @param string|array  $paths
     * @param bool|string $override True to add path as override, string
     * @param bool $force    True to add paths even if them do not exist.
     * @deprecated
     */
    public function addPath($scheme, $prefix, $paths, $override = false, $force = false)
    {
        $this->registerStream($scheme, $prefix, $paths);
    }

    /**
     * Unregister the specified stream
     *
     * @param  string $scheme The stream scheme
     * @return $this
     */
    public function removeStream($scheme)
    {
        if (isset($this->streams[$scheme])) {
            $this->unsetStreamWrapper($scheme);
            unset($this->streams[$scheme]);
        }

        return $this;
    }

    /**
     * @param string $scheme The stream scheme
     * @return ResourceStream
     * @throws StreamNotFoundException If stream is not registered
     */
    public function getStream($scheme)
    {
        if ($this->schemeExist($scheme)) {
            return $this->streams[$scheme];
        } else {
            throw new StreamNotFoundException;
        }
    }

    /**
     * @return array
     */
    public function getStreams()
    {
        return $this->streams;
    }

    /**
     * Return a list of all the stream scheme registered
     * @return array An array of registered scheme => location
     */
    public function listStreams()
    {
        return array_keys($this->streams);
    }

    /**
     * Returns true if a stream has been defined
     *
     * @param  string $scheme The stream scheme
     * @return bool
     */
    public function schemeExist($scheme)
    {
        return isset($this->streams[$scheme]);
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
     * Get a location instance based on it's name
     *
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
     * Get a a list of all registered locations
     *
     * @return array
     */
    public function getLocations()
    {
        return array_reverse($this->locations);
    }

    /**
     * Return a list of all the locations registered by name
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
     * @param  string $uri   Input URI to be searched (can be a file/path)
     * @param  bool   $first Whether to return first path even if it doesn't exist.
     * @return Resource
     */
    public function getResource($uri, $first = false)
    {
        return $this->findCached($uri, false, $first);
    }

    /**
     * Return a list of resources instances
     *
     * @param  string $uri Input URI to be searched (can be a file/path)
     * @param  bool   $all Whether to return all paths even if they don't exist.
     * @return array  Array of Resource
     */
    public function getResources($uri, $all = false)
    {
        return $this->findCached($uri, true, $all);
    }

    /**
     * List all ressources found at a given uri.
     * Same as listing all file in a directory, except here all topmost
     * ressources will be returned when considering all locations
     *
     * @param string $uri Input URI to be searched (can be a uri/path ONLY)
     * @return array The ressources list
     */
    public function listResources($uri)
    {
        $list = [];
        $directories = $this->getResources($uri);
        $directories = array_reverse($directories);

        foreach ($directories as $directory) {
            $files = $this->filesystem->allFiles($directory->getAbsolutePath());
            foreach ($files as $file) {

                // Calculate the relative path
                $fullPath = $file->getPathname();
                $relPath = str_replace($this->basePath, '', $fullPath);
                $relPath = ltrim($relPath, '/');

                // Create the ressource and add it to the list
                $resource = new Resource($directory->getStream(), $directory->getLocation(), $fullPath, $relPath);
                $list[$file->getFilename()] = $resource;
            }
        }

        return array_values($list);
    }

    /**
     * Reset locator by removing all the registered streams and locations.
     *
     * @return $this
     */
    public function reset()
    {
        $this->streams = [];
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
        return $this->schemeExist($scheme);
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
        $resource = $this->getResource($uri, $first);

        if (!$resource) {
            return false;
        }

        if ($absolute) {
            return $resource->getAbsolutePath();
        } else {
            return $resource->getRelPath();
        }
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
        $reources = $this->getResources($uri, $all);

        $paths = [];
        foreach ($reources as $resource) {
            if ($absolute) {
                $paths[] = $resource->getAbsolutePath();
            } else {
                $paths[] = $resource->getRelPath();
            }
        }
        return $paths;
    }

    /**
     * Find a resource from the cached properties
     *
     * @param  string $uri Input URI to be searched (file or directory)
     * @param  bool $array Return an array or a single path
     * @param  bool $all Whether to return all paths even if they don't exist.
     * @return array|string The ressource path or an array of all the ressources path
     */
    protected function findCached($uri, $array, $all)
    {
        // Validate arguments until php7 comes around
        if (!is_string($uri)) {
            throw new \BadMethodCallException('Invalid parameter $uri.');
        }

        // Local caching: make sure that the function gets only called at once for each file.
        // We create a key based on the submitted arguments
        $key = $uri .'@'. (int) $array . (int) $all;

        if (!isset($this->cache[$key])) {
            try {
                list ($scheme, $file) = $this->normalize($uri, true, true);
                if (!$file && $scheme === 'file') {
                    $file = $this->basePath;
                }
                $this->cache[$key] = $this->find($scheme, $file, $array, $all);
            } catch (\BadMethodCallException $e) {
                // If something couldn't be found, return false or empty array
                $this->cache[$key] =  $array ? [] : false;
            }
        }
        return $this->cache[$key];
    }

    /**
     * Build the search path out of the defined strean and locations.
     * If the scheme is shared, we don't need to involve locations and can return it's path directly
     *
     * @param  ResourceStream $stream The stream to search for
     * @return array The search paths based on this stream and all available locations
     */
    protected function searchPaths(ResourceStream $stream)
    {
        // Stream is shared. We return it's value
        if ($stream->isShared()) {
            return [$stream->getPath() => null];
        }

        $list = [];
        foreach ($this->getLocations() as $location) {
            $path = trim($location->getPath(), '/') . '/' . trim($stream->getPath(), '/');
            $list[$path] = $location;
        }

        return $list;
    }

    /**
     * Returns path of a file (or directory) based on a search uri
     *
     * @param  string $scheme The scheme to search in
     * @param  string $file The file to search for
     * @param  bool $array Return an array or a single path
     * @param  bool $all Whether to return all paths even if they don't exist.
     * @throws \InvalidArgumentException
     * @return string|array Found
     */
    protected function find($scheme, $file, $array, $all)
    {
        // Make sure stream exist
        if (!$this->schemeExist($scheme)) {
            throw new \InvalidArgumentException("Invalid resource {$scheme}://");
        }

        // Prepare result depending on $array parameter
        $results = $array ? [] : false;

        foreach ($this->streams[$scheme] as $prefix => $streams) {

            // Make sure the prefix match
            if ($prefix && strpos($file, $prefix) !== 0) {
                continue;
            }

            foreach ($streams as $stream) {

                // Get all search paths using all locations
                $paths = $this->searchPaths($stream);

                // Get filename
                // Remove prefix from filename.
                $filename = '/' . trim(substr($file, strlen($prefix)), '\/');

                // Pass each search paths
                foreach ($paths as $path => $location) {

                    // Check if path from the ResourceStream is absolute or relative
                    // for both unix and windows
                    if (!preg_match('`^/|\w+:`', $path)) {
                        // Handle relative path lookup.
                        $relPath = trim($path . $filename, '/');
                        $fullPath = $this->basePath . '/' . $relPath;
                    } else {
                        // Handle absolute path lookup.
                        $relPath = null; // Can't have a relative path if an absolute one was found
                        $fullPath = rtrim($path . $filename, '/');
                    }

                    // Add the result to the list if the path exist, unless we want all results
                    if ($all || $this->filesystem->exists($fullPath)) {
                        $currentResource = new Resource($stream, $location, $fullPath, $relPath);
                        if (!$array) {
                            return $currentResource;
                        }
                        $results[] = $currentResource;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * @return StreamBuilder
     */
    public function getStreamBuilder()
    {
        return $this->streamBuilder;
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