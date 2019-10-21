<?php

/*
 * UserFrosting Uniform Resource Locator (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @copyright Copyright (c) 2013-2019 Alexander Weissman, Louis Charette
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

/**
 * ResourceLocationInterface Interface.
 *
 * @author    Louis Charette
 */
interface ResourceLocationInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $name
     *
     * @return static
     */
    public function setName(string $name): ResourceLocationInterface;

    /**
     * @return string
     */
    public function getPath(): string;

    /**
     * @param string $path
     *
     * @return static
     */
    public function setPath(?string $path = null): ResourceLocationInterface;
}
