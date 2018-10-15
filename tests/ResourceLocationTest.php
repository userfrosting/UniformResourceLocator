<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

use PHPUnit\Framework\TestCase;

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
        $this->assertInstanceOf(ResourceLocation::Class, $location);
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
}