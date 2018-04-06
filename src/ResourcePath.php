<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

/**
 * ResourcePath Class
 *
 * [Description]
 *
 * @author    Louis Charette
 */
class ResourcePath
{
    /**
     * @var string The path scheme
     */
    protected $scheme;

    /**
     * @var string The base path
     */
    protected $path;

    /**
     * @var bool Is the path shared? If yes, it won't be affected by locations
     */
    protected $shared;

    public function __construct()
    {

    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @param string $scheme
     *
     * @return static
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
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
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return bool
     */
    public function getShared()
    {
        return $this->shared;
    }

    /**
     * @param bool $shared
     *
     * @return static
     */
    public function setShared($shared)
    {
        $this->shared = $shared;
        return $this;
    }
}