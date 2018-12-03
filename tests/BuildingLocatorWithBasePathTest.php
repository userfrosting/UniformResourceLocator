<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator\Tests;

/**
 * Tests for ResourceLocator
 */
class BuildingLocatorWithBasePathTest extends BuildingLocatorTest
{
    /** @var string $basePath */
    protected $basePath = __DIR__ . '/Building';

    /**
     * @return string
     */
    protected function getBasePath()
    {
        return $this->basePath . '/';
    }
}
