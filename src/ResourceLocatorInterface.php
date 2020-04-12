<?php

/*
 * UserFrosting Uniform Resource Locator (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @copyright Copyright (c) 2013-2019 Alexander Weissman, Louis Charette
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

use RocketTheme\Toolbox\ResourceLocator\ResourceLocatorInterface as BaseResourceLocatorInterface;
use UserFrosting\UniformResourceLocator\Exception\LocationNotFoundException;
use UserFrosting\UniformResourceLocator\Exception\StreamNotFoundException;

/**
 * ResourceLocatorInterface Class.
 *
 * @author    Louis Charette
 */
interface ResourceLocatorInterface extends BaseResourceLocatorInterface
{
    /**
     * @param string $uri
     *
     * @throws \BadMethodCallException
     *
     * @return string|bool
     */
    public function __invoke($uri);

    /**
     * Add an exisitng ResourceStream to the stream list.
     *
     * @param ResourceStreamInterface $stream
     *
     * @return static
     */
    public function addStream(ResourceStreamInterface $stream);

    /**
     * Register a new stream.
     *
     * @param string               $scheme
     * @param string               $prefix (default '')
     * @param string|string[]|null $paths  (default null). When using null path, the scheme will be used as a path
     * @param bool                 $shared (default false) Shared resoureces are not affected by locations
     *
     * @return static
     */
    public function registerStream(string $scheme, string $prefix = '', $paths = null, bool $shared = false);

    /**
     * Unregister the specified stream.
     *
     * @param string $scheme The stream scheme
     *
     * @return static
     */
    public function removeStream(string $scheme);

    /**
     * Return information about a specfic stream.
     * Return value is an array of ResourceStreamInterface, for each prefix
     * For example :
     *   $array = array(
     *      ''       => ResourceStreamInterface,
     *      'prefix' => ResourceStreamInterface
     *   );.
     *
     * @param string $scheme The stream scheme
     *
     * @throws StreamNotFoundException If stream is not registered
     *
     * @return array<string,array<ResourceStreamInterface>>
     */
    public function getStream(string $scheme): array;

    /**
     * Return information about a all registered stream.
     * Return value is an array of array of ResourceStreamInterface, for each prefix
     * For example :
     *   'bar' => array(
     *      ''       => ResourceStreamInterface,
     *      'prefix' => ResourceStreamInterface
     *   ),
     *   'foo' => array(
     *      ''       => ResourceStreamInterface,
     *      'blah'   => ResourceStreamInterface
     *   );.
     *
     * @return array<string,array<string,array<ResourceStreamInterface>>>
     */
    public function getStreams(): array;

    /**
     * Return a list of all the stream scheme registered.
     *
     * @return string[] An array of registered scheme => location
     */
    public function listStreams();

    /**
     * Returns true if a stream has been defined.
     *
     * @param string $scheme The stream scheme
     *
     * @return bool
     */
    public function schemeExists(string $scheme);

    /**
     * Add an existing RessourceLocation instance to the location list.
     *
     * @param ResourceLocationInterface $location
     *
     * @return static
     */
    public function addLocation(ResourceLocationInterface $location);

    /**
     * Register a new location.
     *
     * @param string $name The location name
     * @param string $path The location base path (default null)
     *
     * @return static
     */
    public function registerLocation(string $name, ?string $path = null);

    /**
     * Unregister the specified location.
     *
     * @param string $name The location name
     *
     * @return static
     */
    public function removeLocation(string $name);

    /**
     * Get a location instance based on it's name.
     *
     * @param string $name The location name
     *
     * @throws LocationNotFoundException If location is not registered
     *
     * @return ResourceLocationInterface
     */
    public function getLocation(string $name);

    /**
     * Get a a list of all registered locations.
     *
     * @return ResourceLocationInterface[]
     */
    public function getLocations();

    /**
     * Return a list of all the locations registered by name.
     *
     * @return string[] An array of registered name => location
     */
    public function listLocations();

    /**
     * Returns true if a location has been defined.
     *
     * @param string $name The location name
     *
     * @return bool
     */
    public function locationExist(string $name): bool;

    /**
     * Return a resource instance.
     *
     * @param string $uri   Input URI to be searched (can be a file/path)
     * @param bool   $first Whether to return first path even if it doesn't exist.
     *
     * @return ResourceInterface|bool Returns false if resource is not found
     */
    public function getResource(string $uri, bool $first = false);

    /**
     * Return a list of resources instances.
     *
     * @param string $uri Input URI to be searched (can be a file/path)
     * @param bool   $all Whether to return all paths even if they don't exist.
     *
     * @return ResourceInterface[] Array of Resources
     */
    public function getResources(string $uri, bool $all = false): array;

    /**
     * List all ressources found at a given uri.
     * Same as listing all file in a directory, except here all topmost
     * ressources will be returned when considering all locations.
     *
     * @param string $uri  Input URI to be searched (can be a uri/path ONLY)
     * @param bool   $all  If true, all resources will be returned, not only topmost ones
     * @param bool   $sort Set to true to sort results alphabetically by absolute path. Set to false to sort by absolute priority, higest location first. Default to true.
     *
     * @return ResourceInterface[] The ressources list
     */
    public function listResources(string $uri, bool $all = false, bool $sort = true);

    /**
     * @return string
     */
    public function getBasePath();
}
