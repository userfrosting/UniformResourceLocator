<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

use PHPUnit\Framework\TestCase;
use UserFrosting\UniformResourceLocator\ResourceLocator;

/**
 * Tests for ResourceLocator
 */
class ResourceLocatorTest extends TestCase
{
    /**
     * Test the base instance
     */
    public function testInstance()
    {
        $locator = new ResourceLocator('/foo');
        $this->assertInstanceOf(ResourceLocator::class, $locator);
        $this->assertEquals('/foo', $locator->getBasePath());

        $locator->setBasePath('/bar');
        $this->assertEquals('/bar', $locator->getBasePath());
    }

    /**
     * Test the provided "Building" file structure
     */
    public function testBuildingLocator()
    {
        $locator = new ResourceLocator(__DIR__ . '/Building');

        // Register the 4 floors and garage
        $locator->registerLocation('Garage', 'Garage', true);
        $locator->registerLocation('Floor1', 'Floor');
        $locator->registerLocation('Floor2');
        $floor3Location = new ResourceLocation('Floor3');
        $locator->addLocation($floor3Location);

        // Get a list of paths back and test the result
        $locations = $locator->getLocationsList();
        print_r($locations);
        $this->assertEquals([], $locations);

        // Register the car and file path/uri
    }
}