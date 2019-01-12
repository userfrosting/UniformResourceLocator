<?php
/**
 * UserFrosting Uniform Resource Locator (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @copyright Copyright (c) 2013-2019 Alexander Weissman, Louis Charette
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

/**
 * ResourceLocation Class
 *
 * The representation of a location
 *
 * @author    Louis Charette
 */
class ResourceLocation
{
    /**
     * @var string The name of the location
     */
    protected $name;

    /**
     * @var string The base path of the location
     */
    protected $path;

    /**
     * @var string $separator Directory separator
     */
    protected $separator = '/';

    /**
     * Constructor
     *
     * @param string      $name
     * @param string|null $path
     */
    public function __construct($name, $path = null)
    {
        if (is_null($path)) {
            $path = $name;
        }

        $this->setName($name);
        $this->setPath($path);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return static
     */
    public function setPath($path = null)
    {
        $this->path = rtrim($path, $this->separator);

        return $this;
    }
}
