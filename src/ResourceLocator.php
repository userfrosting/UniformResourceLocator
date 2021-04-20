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
use Illuminate\Support\Arr;
use InvalidArgumentException;
use RocketTheme\Toolbox\StreamWrapper\Stream;
use RocketTheme\Toolbox\StreamWrapper\StreamBuilder;
use UserFrosting\UniformResourceLocator\Exception\LocationNotFoundException;
use UserFrosting\UniformResourceLocator\Exception\StreamNotFoundException;

/**
 * ResourceLocator Class.
 *
 * The locator is used to find resources.
 *
 * @author Louis Charette
 */
class ResourceLocator implements ResourceLocatorInterface
{
    /**
     * The list of registered streams ::
     * [
     *      'stream_name' => [
     *          'prefix' => [
     *              ResourceStreamInterface
     *          ]
     *      ]
     * ].
     *
     * @var array<string,array<string,array<ResourceStreamInterface>>>
     */
    protected $streams = [];

    /**
     * @var ResourceLocationInterface[] The list of registered locations
     */
    protected $locations = [];

    /**
     * @var array<ResourceInterface[]|ResourceInterface|false> Locale cache store of found resources
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
     * @var string Directory separator.
     *             N.B.: Will always be `/` regardless of the OS, as they are all added after normalization.
     */
    protected $separator = '/';

    /**
     * @var StreamBuilder
     */
    protected $streamBuilder;

    /**
     * @var string[] List of system reserved streams
     */
    protected $reservedStreams = ['file'];

    /**
     * Constructor.
     *
     * @param string $basePath (default '')
     */
    public function __construct(string $basePath = '')
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
     * {@inheritdoc}
     */
    public function __invoke($uri)
    {
        return $this->findResource($uri, true);
    }

    /**
     * {@inheritdoc}
     */
    public function addStream(ResourceStreamInterface $stream): self
    {
        if (in_array($stream->getScheme(), $this->reservedStreams)) {
            throw new InvalidArgumentException("Can't add restricted stream scheme {$stream->getScheme()}.");
        }

        $this->streams[$stream->getScheme()][$stream->getPrefix()][] = $stream;
        $this->setupStreamWrapper($stream->getScheme());

        // Sort in reverse order to get longer prefixes to be matched first.
        krsort($this->streams[$stream->getScheme()]);

        return $this;
    }

    /**
     * Register the scheme as a php stream wrapper.
     *
     * @param string $scheme The stream scheme
     */
    protected function setupStreamWrapper(string $scheme): void
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
     * Unset a php stream wrapper.
     *
     * @param string $scheme The stream scheme
     */
    protected function unsetStreamWrapper(string $scheme): void
    {
        $this->streamBuilder->remove($scheme);

        if (in_array($scheme, stream_get_wrappers())) {
            stream_wrapper_unregister($scheme);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerStream(string $scheme, string $prefix = '', $paths = null, bool $shared = false): self
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
     * Register a new shared stream.
     * Shortcut for registerStream with $shared flag set to true.
     *
     * @param string               $scheme
     * @param string               $prefix (default '')
     * @param string|string[]|null $paths  (default null). When using null path, the scheme will be used as a path
     *
     * @return static
     */
    public function registerSharedStream(string $scheme, string $prefix = '', $paths = null): self
    {
        return $this->registerStream($scheme, $prefix, $paths, true);
    }

    /**
     * AddPath function. Used to preserve compatibility with RocketTheme/Toolbox.
     *
     * @param string          $scheme
     * @param string          $prefix
     * @param string|string[] $paths
     * @param bool|string     $override Not used. Kept for backward compatibility.
     * @param bool            $force    Not used. Kept for backward compatibility.
     *
     * @deprecated
     */
    public function addPath(string $scheme, string $prefix, $paths, $override = false, bool $force = false): void
    {
        $this->registerStream($scheme, $prefix, $paths);
    }

    /**
     * {@inheritdoc}
     */
    public function removeStream(string $scheme): self
    {
        if (isset($this->streams[$scheme])) {
            $this->unsetStreamWrapper($scheme);
            unset($this->streams[$scheme]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getStream(string $scheme): array
    {
        if ($this->schemeExists($scheme)) {
            return $this->streams[$scheme];
        } else {
            throw new StreamNotFoundException();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStreams(): array
    {
        return $this->streams;
    }

    /**
     * {@inheritdoc}
     */
    public function listStreams(): array
    {
        return array_keys($this->streams);
    }

    /**
     * {@inheritdoc}
     */
    public function schemeExists(string $scheme): bool
    {
        return isset($this->streams[$scheme]);
    }

    /**
     * {@inheritdoc}
     */
    public function addLocation(ResourceLocationInterface $location): self
    {
        $this->locations[$location->getName()] = $location;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function registerLocation(string $name, ?string $path = null): self
    {
        $location = new ResourceLocation($name, $path);
        $this->addLocation($location);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeLocation(string $name): self
    {
        unset($this->locations[$name]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocation(string $name): ResourceLocationInterface
    {
        if ($this->locationExist($name)) {
            return $this->locations[$name];
        } else {
            throw new LocationNotFoundException();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLocations(): array
    {
        return array_reverse($this->locations);
    }

    /**
     * {@inheritdoc}
     */
    public function listLocations(): array
    {
        return array_keys(array_reverse($this->locations));
    }

    /**
     * {@inheritdoc}
     */
    public function locationExist(string $name): bool
    {
        return isset($this->locations[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getResource(string $uri, bool $first = false)
    {
        return $this->findCached($uri, false, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function getResources(string $uri, bool $all = false): array
    {
        return $this->findCached($uri, true, $all);
    }

    /**
     * {@inheritdoc}
     */
    public function listResources(string $uri, bool $all = false, bool $sort = true): array
    {
        $list = [];

        // Get all directory where we can find this ressource. Will be returned with the priority order
        $directories = $this->getResources($uri);

        foreach ($directories as $directory) {

            // Use Filesystem to list all file in the directory
            $files = $this->filesystem->allFiles($directory->getAbsolutePath());

            // Sort files. Filesystem can return inconsistant order sometime
            // Files will be sorted alphabetically inside a location even if don't resort later across all sprinkles
            $files = Arr::sort($files, function ($resource) {
                return $resource->getRealPath();
            });

            foreach ($files as $file) {

                // Calculate the relative path
                $basePath = rtrim($this->getBasePath(), $this->separator).$this->separator;
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
            $list = Arr::sort($list, function ($resource) {
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
    public function reset(): self
    {
        $this->streams = [];
        $this->locations = [];

        return $this;
    }

    /**
     * Returns true if uri is resolvable by using locator.
     *
     * @param string $uri URI to test
     *
     * @return bool True if is resolvable
     */
    public function isStream($uri): bool
    {
        try {
            list($scheme) = Normalizer::normalize($uri, true, true);
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
     * @param string $uri      Input URI to be searched (can be a file or directory)
     * @param bool   $absolute Whether to return absolute path.
     * @param bool   $first    Whether to return first path even if it doesn't exist.
     *
     * @throws \BadMethodCallException
     *
     * @return string|bool The ressource path, or false if not found resource
     */
    public function findResource($uri, $absolute = true, $first = false)
    {
        $resource = $this->getResource($uri, $first);

        if (!$resource instanceof ResourceInterface) {
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
     * @param string $uri      Input URI to be searched (can be a file or directory)
     * @param bool   $absolute Whether to return absolute path.
     * @param bool   $all      Whether to return all paths even if they don't exist.
     *
     * @return string[] An array of all the ressources path
     */
    public function findResources($uri, $absolute = true, $all = false)
    {
        $resources = $this->getResources($uri, $all);

        $paths = [];
        foreach ($resources as $resource) {
            if ($absolute) {
                $paths[] = $resource->getAbsolutePath();
            } else {
                $paths[] = $resource->getPath();
            }
        }

        return $paths;
    }

    /**
     * Find a resource from the cached properties.
     *
     * @param string $uri   Input URI to be searched (file or directory)
     * @param bool   $array Return an array or a single path
     * @param bool   $all   Whether to return all paths even if they don't exist.
     *
     * @return ResourceInterface[]|ResourceInterface|false The ressource path or an array of all the ressources path or false if not resource can be found
     */
    protected function findCached(string $uri, bool $array, bool $all)
    {
        // Local caching: make sure that the function gets only called at once for each file.
        // We create a key based on the submitted arguments
        $key = $uri.'@'.(int) $array.(int) $all;

        if (!isset($this->cache[$key])) {
            try {
                list($scheme, $file) = Normalizer::normalize($uri, true, true);
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
     * If the scheme is shared, we don't need to involve locations and can return it's path directly.
     *
     * @param ResourceStreamInterface $stream The stream to search for
     *
     * @return array<string,ResourceLocationInterface|null> The search paths based on this stream and all available locations
     */
    protected function searchPaths(ResourceStreamInterface $stream): array
    {
        // Stream is shared. We return it's value
        if ($stream->isShared()) {
            return [$stream->getPath() => null];
        }

        $list = [];
        foreach ($this->getLocations() as $location) {

            // Get location and stream path
            $parts = [];
            $parts[] = rtrim($location->getPath(), $this->separator);
            $parts[] = trim($stream->getPath(), $this->separator);

            // Merge both paths. Array_filter will take
            $path = implode($this->separator, array_filter($parts));

            $list[$path] = $location;
        }

        return $list;
    }

    /**
     * Returns path of a file (or directory) based on a search uri.
     *
     * @param string $scheme The scheme to search in
     * @param string $file   The file to search for
     * @param bool   $array  Return an array or a single path
     * @param bool   $all    Whether to return all paths even if they don't exist.
     *
     * @throws InvalidArgumentException
     *
     * @return ResourceInterface|ResourceInterface[]
     */
    protected function find(string $scheme, string $file, bool $array, bool $all)
    {
        // Make sure stream exist
        if (!$this->schemeExists($scheme)) {
            throw new InvalidArgumentException("Scheme {$scheme}:// doesn't exist.");
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
                $filename = $this->separator.trim(substr($file, strlen($prefix)), '\/');

                // Pass each search paths
                foreach ($paths as $path => $location) {
                    $basePath = rtrim($this->getBasePath(), $this->separator).$this->separator;

                    // Check if path from the ResourceStream is absolute or relative
                    // for both unix and windows
                    if (!preg_match('`^/|\w+:`', $path)) {
                        // Handle relative path lookup.
                        $relPath = trim($path.$filename, $this->separator);
                        $fullPath = $basePath.$relPath;
                    } else {
                        // Handle absolute path lookup.
                        $fullPath = rtrim($path.$filename, $this->separator);
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
    public function getStreamBuilder(): StreamBuilder
    {
        return $this->streamBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @param string $basePath
     *
     * @return static
     */
    public function setBasePath(string $basePath): self
    {
        $this->basePath = Normalizer::normalizePath($basePath);

        return $this;
    }
}
