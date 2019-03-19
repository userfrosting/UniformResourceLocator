<?php

/*
 * UserFrosting Uniform Resource Locator (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @copyright Copyright (c) 2013-2019 Alexander Weissman, Louis Charette
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

use Illuminate\Filesystem\Filesystem;
use UserFrosting\UniformResourceLocator\Exception\LocationNotFoundException;
use UserFrosting\UniformResourceLocator\Exception\StreamNotFoundException;
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
     * @var string $separator Directory separator
     */
    protected $separator = '/';

    /**
     * @var StreamBuilder
     */
    protected $streamBuilder;

    /**
     * @var array List of system reserved streams
     */
    protected $reservedStreams = ['file'];

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
        $this->filesystem = new Filesystem();

        // Setup stream
        Stream::setLocator($this);

        // Get the builder
        $this->streamBuilder = new StreamBuilder();
    }

    /**
     * @param  string                  $uri
     * @throws \BadMethodCallException
     * @return string|bool
     */
    public function __invoke($uri)
    {
        return $this->findResource($uri, true);
    }

    /**
     * Add an exisitng ResourceStream to the stream list
     *
     * @param  ResourceStreamInterface $stream
     * @return static
     */
    public function addStream(ResourceStreamInterface $stream)
    {
        if (in_array($stream->getScheme(), $this->reservedStreams)) {
            throw new \InvalidArgumentException("Can't add restriced stream scheme {$stream->getScheme()}.");
        }

        $this->streams[$stream->getScheme()][$stream->getPrefix()][] = $stream;
        $this->setupStreamWrapper($stream->getScheme());

        // Sort in reverse order to get longer prefixes to be matched first.
        krsort($this->streams[$stream->getScheme()]);

        return $this;
    }

    /**
     * Register the scheme as a php stream wrapper
     *
     * @param string $scheme The stream scheme
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
     * @param  string            $scheme
     * @param  string            $prefix (default '')
     * @param  string|array|null $paths  (default null). When using null path, the scheme will be used as a path
     * @param  bool              $shared (default false) Shared ressources are not affected by locations
     * @return static
     */
    public function registerStream($scheme, $prefix = '', $paths = null, $shared = false)
    {
        if (is_null($paths)) {
            $stream = new ResourceStream($scheme, $prefix, null, $shared);
            $this->addStream($stream);
        } else {

            // Invert arrays list. Last path has priority
            $paths = array_reverse((array) $paths);
            foreach ($paths as $path) {
                $stream = new ResourceStream($scheme, $prefix, $path, $shared);
                $this->addStream($stream);
            }
        }

        return $this;
    }

    /**
     * AddPath function. Used to preserve compatibility with RocketTheme/Toolbox
     *
     * @param string       $scheme
     * @param string       $prefix
     * @param string|array $paths
     * @param bool|string  $override True to add path as override, string
     * @param bool         $force    True to add paths even if them do not exist.
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
     * @return static
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
     * @param  string                  $scheme The stream scheme
     * @throws StreamNotFoundException If stream is not registered
     * @return ResourceStreamInterface
     */
    public function getStream($scheme)
    {
        if ($this->schemeExists($scheme)) {
            return $this->streams[$scheme];
        } else {
            throw new StreamNotFoundException();
        }
    }

    /**
     * @return array[ResourceStreamInterface]
     */
    public function getStreams()
    {
        return $this->streams;
    }

    /**
     * Return a list of all the stream scheme registered
     * @return array[string] An array of registered scheme => location
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
    public function schemeExists($scheme)
    {
        return isset($this->streams[$scheme]);
    }

    /**
     * Add an existing RessourceLocation instance to the location list
     *
     * @param ResourceLocationInterface $location
     */
    public function addLocation(ResourceLocationInterface $location)
    {
        $this->locations[$location->getName()] = $location;

        return $this;
    }

    /**
     * Register a new location
     *
     * @param  string $name The location name
     * @param  string $path The location base path (default null)
     * @return static
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
     * @return static
     */
    public function removeLocation($name)
    {
        unset($this->locations[$name]);

        return $this;
    }

    /**
     * Get a location instance based on it's name
     *
     * @param  string                    $name The location name
     * @throws LocationNotFoundException If location is not registered
     * @return ResourceLocationInterface
     */
    public function getLocation($name)
    {
        if ($this->locationExist($name)) {
            return $this->locations[$name];
        } else {
            throw new LocationNotFoundException();
        }
    }

    /**
     * Get a a list of all registered locations
     *
     * @return array[ResourceLocationInterface]
     */
    public function getLocations()
    {
        return array_reverse($this->locations);
    }

    /**
     * Return a list of all the locations registered by name
     *
     * @return array[string] An array of registered name => location
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
     * @param  string            $uri   Input URI to be searched (can be a file/path)
     * @param  bool              $first Whether to return first path even if it doesn't exist.
     * @return ResourceInterface
     */
    public function getResource($uri, $first = false)
    {
        return $this->findCached($uri, false, $first);
    }

    /**
     * Return a list of resources instances
     *
     * @param  string                   $uri Input URI to be searched (can be a file/path)
     * @param  bool                     $all Whether to return all paths even if they don't exist.
     * @return array[ResourceInterface] Array of Resource
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
     * @param  string                   $uri  Input URI to be searched (can be a uri/path ONLY)
     * @param  bool                     $all  If true, all resources will be returned, not only topmost ones
     * @param  bool                     $sort Set to true to sort results alphabetically by absolute path. Set to false to sort by absolute priority, higest location first. Default to true.
     * @return array[ResourceInterface] The ressources list
     */
    public function listResources($uri, $all = false, $sort = true)
    {
        $list = [];

        // Get all directory where we can find this ressource. Will be returned with the priority order
        $directories = $this->getResources($uri);

        foreach ($directories as $directory) {

            // Use Filesystem to list all file in the directory
            $files = $this->filesystem->allFiles($directory->getAbsolutePath());

            // Sort files. Filesystem can return inconsistant order sometime
            // Files will be sorted alphabetically inside a location even if don't resort later across all sprinkles
            $files = array_sort($files, function ($resource) {
                return $resource->getRealPath();
            });

            foreach ($files as $file) {

                // Calculate the relative path
                $basePath = rtrim($this->getBasePath(), $this->separator) . $this->separator;
                $fullPath = $file->getPathname();
                $relPath = str_replace($basePath, '', $fullPath);
                $relPath = ltrim($relPath, $this->separator);

                // Create the ressource and add it to the list
                $resource = new Resource($directory->getStream(), $directory->getLocation(), $relPath, $basePath);

                if ($all) {
                    // Add all files to the list
                    $list[] = $resource;
                } else {
                    // Add file to the list it it's not already there from an higher priority location
                    if (!isset($list[$resource->getUri()])) {
                        $list[$resource->getUri()] = $resource;
                    }
                }
            }
        }

        // Apply global sorting if required. This will return all resources sorted
        // alphabetically instead of by priority
        if ($sort) {
            $list = array_sort($list, function ($resource) {
                return $resource->getAbsolutePath();
            });
        }

        return array_values($list);
    }

    /**
     * Reset locator by removing all the registered streams and locations.
     *
     * @return static
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
     * @param  string                  $uri
     * @param  bool                    $throwException
     * @param  bool                    $splitStream
     * @throws \BadMethodCallException
     * @return string|array|bool
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
        $uri = preg_replace('|\\\|u', $this->separator, $uri);
        $segments = explode('://', $uri, 2);
        $path = array_pop($segments);
        $scheme = array_pop($segments) ?: '';
        if ($path) {
            $path = preg_replace('|\\\|u', $this->separator, $path);
            $parts = explode($this->separator, $path);
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
            $path = implode($this->separator, $list);
        }

        return $splitStream ? [$scheme, $path] : ($scheme !== '' ? "{$scheme}://{$path}" : $path);
    }

    /**
     * Returns true if uri is resolvable by using locator.
     *
     * @param  string $uri URI to test
     * @return bool   True if is resolvable
     */
    public function isStream($uri)
    {
        try {
            list($scheme) = $this->normalize($uri, true, true);
        } catch (\Exception $e) {
            return false;
        }

        return $this->schemeExists($scheme);
    }

    /**
     * Find highest priority instance from a resource. Return the path for said resource
     * For example, if looking for a `test.json` ressource, only the top priority
     * instance of `test.json` found will be returned.
     *
     * @param  string                  $uri      Input URI to be searched (can be a file or directory)
     * @param  bool                    $absolute Whether to return absolute path.
     * @param  bool                    $first    Whether to return first path even if it doesn't exist.
     * @throws \BadMethodCallException
     * @return string                  The ressource path
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
            return $resource->getPath();
        }
    }

    /**
     * Find all instances from a resource. Return an array of paths for said resource
     * For example, if looking for a `test.json` ressource, all instance
     * of `test.json` found will be listed.
     *
     * @param  string        $uri      Input URI to be searched (can be a file or directory)
     * @param  bool          $absolute Whether to return absolute path.
     * @param  bool          $all      Whether to return all paths even if they don't exist.
     * @return array[string] An array of all the ressources path
     */
    public function findResources($uri, $absolute = true, $all = false)
    {
        $reources = $this->getResources($uri, $all);

        $paths = [];
        foreach ($reources as $resource) {
            if ($absolute) {
                $paths[] = $resource->getAbsolutePath();
            } else {
                $paths[] = $resource->getPath();
            }
        }

        return $paths;
    }

    /**
     * Find a resource from the cached properties
     *
     * @param  string                                     $uri   Input URI to be searched (file or directory)
     * @param  bool                                       $array Return an array or a single path
     * @param  bool                                       $all   Whether to return all paths even if they don't exist.
     * @return array[ResourceInterface]|ResourceInterface The ressource path or an array of all the ressources path
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
                list($scheme, $file) = $this->normalize($uri, true, true);
                $this->cache[$key] = $this->find($scheme, $file, $array, $all);
            } catch (\BadMethodCallException $e) {
                // If something couldn't be found, return false or empty array
                $this->cache[$key] = $array ? [] : false;
            }
        }

        return $this->cache[$key];
    }

    /**
     * Build the search path out of the defined strean and locations.
     * If the scheme is shared, we don't need to involve locations and can return it's path directly
     *
     * @param  ResourceStreamInterface          $stream The stream to search for
     * @return array[ResourceLocationInterface] The search paths based on this stream and all available locations
     */
    protected function searchPaths(ResourceStreamInterface $stream)
    {
        // Stream is shared. We return it's value
        if ($stream->isShared()) {
            return [$stream->getPath() => null];
        }

        $list = [];
        foreach ($this->getLocations() as $location) {
            $path = rtrim($location->getPath(), $this->separator) . $this->separator . trim($stream->getPath(), $this->separator);
            $list[$path] = $location;
        }

        return $list;
    }

    /**
     * Returns path of a file (or directory) based on a search uri
     *
     * @param  string                                     $scheme The scheme to search in
     * @param  string                                     $file   The file to search for
     * @param  bool                                       $array  Return an array or a single path
     * @param  bool                                       $all    Whether to return all paths even if they don't exist.
     * @throws \InvalidArgumentException
     * @return ResourceInterface|array[ResourceInterface]
     */
    protected function find($scheme, $file, $array, $all)
    {
        // Make sure stream exist
        if (!$this->schemeExists($scheme)) {
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
                $filename = $this->separator . trim(substr($file, strlen($prefix)), '\/');

                // Pass each search paths
                foreach ($paths as $path => $location) {
                    $basePath = rtrim($this->getBasePath(), $this->separator) . $this->separator;

                    // Check if path from the ResourceStream is absolute or relative
                    // for both unix and windows
                    if (!preg_match('`^/|\w+:`', $path)) {
                        // Handle relative path lookup.
                        $relPath = trim($path . $filename, $this->separator);
                        $fullPath = $basePath . $relPath;
                    } else {
                        // Handle absolute path lookup.
                        $fullPath = rtrim($path . $filename, $this->separator);
                        $relPath = str_replace($basePath, '', $fullPath);
                    }

                    // Add the result to the list if the path exist, unless we want all results
                    if ($all || $this->filesystem->exists($fullPath)) {
                        $currentResource = new Resource($stream, $location, $relPath, $basePath);
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
        $this->basePath = $this->normalize($basePath);

        return $this;
    }
}
