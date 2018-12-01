<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator\Tests;

use PHPUnit\Framework\TestCase;
use UserFrosting\UniformResourceLocator\ResourceLocation;

/**
 * Tests for ResourceLocator
 */
class ResourceLocationTest extends TestCase
{
    /**
     * Test ResourceLocation class
     */
    public function testResourceLocation()
    {
        // Test instance & default values
        $location = new ResourceLocation('');
        $this->assertInstanceOf(ResourceLocation::class, $location);
        $this->assertEquals('', $location->getName());
        $this->assertEquals('', $location->getPath());

        // Set/get name & path properties
        $location->setName('foo');
        $this->assertEquals('foo', $location->getName());

        $location->setPath('/bar');
        $this->assertEquals('/bar', $location->getPath());
    }

    /**
     * Now try again with the info in the constructor
     */
    public function testResourceLocation_ctor()
    {
        $location = new ResourceLocation('bar', '/foo');
        $this->assertEquals('bar', $location->getName());
        $this->assertEquals('/foo', $location->getPath());
    }

    /**
     * @depends testResourceLocation_ctor
     */
    public function testResourceLocation_ctorWithSupressesRightSlashe()
    {
        $location = new ResourceLocation('bar', '/foo/');
        $this->assertEquals('bar', $location->getName());
        $this->assertEquals('/foo', $location->getPath());
    }

    /**
    * @depends testResourceLocation_ctor
     */
    public function testResourceLocation_ctoOmittedPathEqualsName()
    {
        $location = new ResourceLocation('bar');
        $this->assertEquals('bar', $location->getName());
        $this->assertEquals('bar', $location->getPath());
    }
}
