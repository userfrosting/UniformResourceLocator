<?php
/**
 * UserFrosting Uniform Resource Locator (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @copyright Copyright (c) 2013-2019 Alexander Weissman, Louis Charette
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

use UserFrosting\UniformResourceLocator\Exception\LocationNotFoundException;
use UserFrosting\UniformResourceLocator\Exception\StreamNotFoundException;
use RocketTheme\Toolbox\ResourceLocator\ResourceLocatorInterface as BaseResourceLocatorInterface;

/**
 * ResourceLocatorInterface Class
 *
 * @author    Louis Charette
 */
interface ResourceLocatorInterface extends BaseResourceLocatorInterface
{
    /**
     * Add an exisitng ResourceStream to the stream list
     *
     * @param ResourceStreamInterface $stream
     */
    public function addStream(ResourceStreamInterface $stream);

    /**
     * Register a new stream
     *
     * @param string            $scheme
     * @param string            $prefix (default '')
     * @param string|array|null $paths  (default null). When using null path, the scheme will be used as a path
     * @param bool              $shared (default false) Shared resoureces are not affected by locations
     */
    public function registerStream($scheme, $prefix = '', $paths = null, $shared = false);

    /**
     * Unregister the specified stream
     *
     * @param  string $scheme The stream scheme
     * @return static
     */
    public function removeStream($scheme);

    /**
     * @param  string                  $scheme The stream scheme
     * @throws StreamNotFoundException If stream is not registered
     * @return ResourceStreamInterface
     */
    public function getStream($scheme);

    /**
     * @return array
     */
    public function getStreams();

    /**
     * Return a list of all the stream scheme registered
     * @return array An array of registered scheme => location
     */
    public function listStreams();

    /**
     * Returns true if a stream has been defined
     *
     * @param  string $scheme The stream scheme
     * @return bool
     */
    public function schemeExists($scheme);

    /**
     * Add an existing RessourceLocation instance to the location list
     *
     * @param ResourceLocationInterface $location
     */
    public function addLocation(ResourceLocationInterface $location);

    /**
     * Register a new location
     *
     * @param  string $name The location name
     * @param  string $path The location base path (default null)
     * @return static
     */
    public function registerLocation($name, $path = null);

    /**
     * Unregister the specified location
     *
     * @param  string $name The location name
     * @return static
     */
    public function removeLocation($name);

    /**
     * Get a location instance based on it's name
     *
     * @param  string                    $name The location name
     * @throws LocationNotFoundException If location is not registered
     * @return ResourceLocationInterface
     */
    public function getLocation($name);

    /**
     * Get a a list of all registered locations
     *
     * @return array
     */
    public function getLocations();

    /**
     * Return a list of all the locations registered by name
     *
     * @return array An array of registered name => location
     */
    public function listLocations();

    /**
     * Returns true if a location has been defined
     *
     * @param  string $name The location name
     * @return bool
     */
    public function locationExist($name);

    /**
     * Return a resource instance
     *
     * @param  string   $uri   Input URI to be searched (can be a file/path)
     * @param  bool     $first Whether to return first path even if it doesn't exist.
     * @return resource
     */
    public function getResource($uri, $first = false);

    /**
     * Return a list of resources instances
     *
     * @param  string $uri Input URI to be searched (can be a file/path)
     * @param  bool   $all Whether to return all paths even if they don't exist.
     * @return array  Array of Resource
     */
    public function getResources($uri, $all = false);

    /**
     * List all ressources found at a given uri.
     *
     * @param  string $uri Input URI to be searched (can be a uri/path ONLY)
     * @param  bool   $all If true, all resources will be returned, not only topmost ones
     * @return array  The ressources list
     */
    public function listResources($uri, $all = false);

    /**
     * @return string
     */
    public function getBasePath();
}
