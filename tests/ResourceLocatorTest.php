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
use UserFrosting\UniformResourceLocator\Exception\LocationNotFoundException;
use UserFrosting\UniformResourceLocator\Exception\PathNotFoundException;

/**
 * Tests for ResourceLocator
 */
class ResourceLocatorTest extends TestCase
{
    /**
     * Test ResourcePath Class
     */
    public function testResourcePath()
    {
        // Test instance & default values
        $path = new ResourcePath;
        $this->assertInstanceOf(ResourcePath::Class, $path);
        $this->assertEquals('', $path->getScheme());
        $this->assertEquals('', $path->getPath());
        $this->assertFalse($path->getShared());

        // Set/get scheme, path and shared properties
        $path->setScheme('foo');
        $this->assertEquals('foo', $path->getScheme());

        $path->setPath('/bar');
        $this->assertEquals('/bar', $path->getPath());

        $path->setShared(true);
        $this->assertTrue($path->getShared());

        // Now try again with the info in the constructor
        $path = new ResourcePath('bar', '/foo', true);
        $this->assertEquals('bar', $path->getScheme());
        $this->assertEquals('/foo', $path->getPath());
        $this->assertTrue($path->getShared());


        // Test streamwrapper
        // !TODO
    }

    /**
     * Test ResourceLocation class
     */
    public function testResourceLocation()
    {
        // Test instance & default values
        $location = new ResourceLocation;
        $this->assertInstanceOf(ResourceLocation::Class, $location);
        $this->assertEquals('', $location->getName());
        $this->assertEquals('', $location->getPath());

        // Set/get name & path properties
        $location->setName('foo');
        $this->assertEquals('foo', $location->getName());

        $location->setPath('/bar');
        $this->assertEquals('/bar', $location->getPath());

        // Now try again with the info in the constructor
        $location = new ResourceLocation('bar', '/foo');
        $this->assertEquals('bar', $location->getName());
        $this->assertEquals('/foo', $location->getPath());
    }

    /**
     * Test ResourceLocator Class
     */
    public function testResourceLocator()
    {
        // Test instance & default values
        $locator = new ResourceLocator;
        $this->assertEquals('', $locator->getBasePath());

        // Set/get basePath properties
        $locator->setBasePath(__DIR__ . '/Building');
        $this->assertEquals(__DIR__ . '/Building', $locator->getBasePath());

        // Now try again with the info in the constructor
        $locator2 = new ResourceLocator(__DIR__ . '/Building');
        $this->assertEquals(__DIR__ . '/Building', $locator2->getBasePath());


        // Test path manipulation...
        $path = new ResourcePath('bar', '/foo');
        $locator->addPath($path);
        $locator->registerPath('foo', '/bar');

        // ...getPath
        $barPath = $locator->getPath('bar');
        $this->assertInstanceOf(ResourcePath::class, $barPath);
        $this->assertEquals('/foo', $barPath->getPath());

        // ...getPaths
        $paths = $locator->getPaths();
        $this->assertInternalType('array', $paths);
        $this->assertCount(2, $paths);
        $this->assertInstanceOf(ResourcePath::class, $paths['bar']);
        $this->assertEquals('/foo', $paths['bar']->getPath());

        // ...getPathsList
        $this->assertEquals(['bar', 'foo'], $locator->getPathsList());

        // ...removePath
        $locator->removePath('bar');
        $this->assertCount(1, $locator->getPaths());

        // ...pathExist
        $this->assertTrue($locator->pathExist('foo'));
        $this->assertFalse($locator->pathExist('bar'));
        $this->assertFalse($locator->pathExist('etc'));


        // Test location manipulation
        $location = new ResourceLocation('foo', '/bar');
        $locator->addLocation($location);
        $locator->registerLocation('bar', '/foo');
        $locator->registerLocation('blah');

        // ...getLocation
        $barLocation = $locator->getLocation('bar');
        $this->assertInstanceOf(ResourceLocation::class, $barLocation);
        $this->assertEquals('/foo', $barLocation->getPath());

        // ...getLocations
        $locations = $locator->getLocations();
        $this->assertInternalType('array', $locations);
        $this->assertCount(3, $locations);
        $this->assertInstanceOf(ResourceLocation::class, $locations['blah']);
        $this->assertEquals('blah', $locations['blah']->getPath());

        // ...getLocationsList
        $this->assertEquals(['foo', 'bar', 'blah'], $locator->getLocationsList());

        // ...removeLocation
        $locator->removeLocation('bar');
        $this->assertCount(2, $locator->getLocations());

        // ...pathExist
        $this->assertTrue($locator->locationExist('foo'));
        $this->assertFalse($locator->locationExist('bar'));
        $this->assertFalse($locator->locationExist('etc'));


        // Test reset
        $locator->reset();
        $this->assertCount(0, $locator->getPaths());
        $this->assertCount(0, $locator->getLocations());
    }


    /**
     * Test PathNotFoundException
     * @expectedException \UserFrosting\UniformResourceLocator\Exception\PathNotFoundException
     */
    public function testPathNotFoundException()
    {
        $locator = new ResourceLocator;
        $locator->getPath('etc');
    }

    /**
     * Test LocationNotFoundException
     * @expectedException \UserFrosting\UniformResourceLocator\Exception\LocationNotFoundException
     */
    public function testLocationNotFoundException()
    {
        $locator = new ResourceLocator;
        $locator->getLocation('etc');
    }

    /**
     * Test the provided "Building" file structure
     */
    public function testBuildingLocator()
    {
        $locator = new ResourceLocator(__DIR__ . '/Building');

        // Register the floors
        $locator->registerLocation('Floor1', 'Floors/Floor');
        $locator->registerLocation('Floor2', 'Floors/Floor2');
        $locator->registerLocation('Floor3', 'Floors/Floor3');

        // Register the paths
        $locator->registerPath('file'); // Search path -> Building/Floors/{floorX}/file
        $locator->registerPath('conf', 'config'); // Search path -> Building/Floors/{floorX}/config
        $locator->registerPath('cars', 'Garage/cars', true); // Search path -> Building/Garage/cars

        

        // We start by gettings cars. Here, we should only get the cars from the Garage.
        // Cars defined by the "Floor3" shoudn't be listed here

        // The config file should never be found when looking for files
    }
}