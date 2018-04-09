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
use UserFrosting\UniformResourceLocator\Resources\ResourceInterface;

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
        $path = new ResourcePath('');
        $this->assertInstanceOf(ResourcePath::Class, $path);
        $this->assertEquals('', $path->getScheme());
        $this->assertEquals('', $path->getPath());
        $this->assertFalse($path->isShared());

        // Set/get scheme, path and shared properties
        $path->setScheme('foo');
        $this->assertEquals('foo', $path->getScheme());

        $path->setPath('/bar');
        $this->assertEquals('/bar', $path->getPath());

        $path->setShared(true);
        $this->assertTrue($path->isShared());

        // Now try again with the info in the constructor
        $path = new ResourcePath('bar', '/foo', true);
        $this->assertEquals('bar', $path->getScheme());
        $this->assertEquals('/foo', $path->getPath());
        $this->assertTrue($path->isShared());

        // When no path is defined, the name should be used
        $path = new ResourcePath('etc');
        $this->assertEquals('etc', $path->getScheme());
        $this->assertEquals('etc', $path->getPath());

        // Test streamwrapper
        // !TODO
    }

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

        // ...listPaths
        $this->assertEquals(['bar', 'foo'], $locator->listPaths());

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

        // ...listLocations
        $this->assertEquals(['blah', 'bar', 'foo'], $locator->listLocations());

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
        $locator->registerPath('files'); // Search path -> Building/Floors/{floorX}/file
        $locator->registerPath('conf', 'config'); // Search path -> Building/Floors/{floorX}/config
        $locator->registerPath('cars', 'Garage/cars', true); // Search path -> Building/Garage/cars

        // Test backward compatibility
        $this->rocketThemeUniformResourceLocatorCompatibility($locator);

        // We start by gettings cars (shared path)
        $this->sharedPathTest($locator);

        // We now looks into the Floors (non-shared path)
        $this->normalPathTest($locator);
    }

    /**
     * subtest for the shared path (Garage) of the "Building" file structure
     * @param ResourceLocator $locator
     */
    protected function sharedPathTest(ResourceLocator $locator)
    {
        // Find the `car.json` location. Should be from the Garage.
        $ress = $locator->findResource('cars://cars.json');
        $this->assertEquals(__DIR__ . '/Building/Garage/cars/cars.json', $ress);
        $this->assertEquals([__DIR__ . '/Building/Garage/cars/cars.json'], $locator->findResources('cars://cars.json'));

        // Should also work with simple path (non file uri)
        $this->assertEquals(__DIR__ . '/Building/Garage/cars', $locator->findResource('cars://'));
        $this->assertEquals([__DIR__ . '/Building/Garage/cars'], $locator->findResources('cars://'));

        // Listing all ressources should only list the Garage one (not the Floor3 one)
        /*
        $this->assertEquals([__DIR__ . '/Building/Garage/cars/cars.json'], $locator->listResources('cars://'));
        */

        // We also test the stream wrapper works
        /*
        $path = $locator->findResource('cars://cars.json');
        $swContent = file_get_contents('cars://cars.json');
        $pathContent = file_get_contents($path);
        $this->assertEquals($swContent, $pathContent);
        */

        // getInstance
        /*
        $resource = $locator->getResource('cars://cars.json');
        $this->assertInstanceOf(ResourceInterface::class, $resource);
        */

        // Test content...
        // Test Path...
        // Test Location...

    }

    /**
     * subtest for the normal path (Floors) of the "Building" file structure
     * @param ResourceLocator $locator
     */
    protected function normalPathTest(ResourceLocator $locator)
    {
        // Looking for the `test.json` file.
        // The config file should never be found when looking for files
        $this->assertEquals(__DIR__ . '/Building/Floors/Floor3/files/test.json', $locator->findResource('files://test.json'));
        $this->assertEquals([
            __DIR__ . '/Building/Floors/Floor3/files/test.json',
            __DIR__ . '/Building/Floors/Floor2/files/test.json',
            __DIR__ . '/Building/Floors/Floor/files/test.json'
        ], $locator->findResources('files://test.json'));

        // Should also work with simple path (non file uri)
        $this->assertEquals(__DIR__ . '/Building/Floors/Floor3/files', $locator->findResource('files://'));
        $this->assertEquals([
            __DIR__ . '/Building/Floors/Floor3/files',
            __DIR__ . '/Building/Floors/Floor2/files',
            __DIR__ . '/Building/Floors/Floor/files'
        ], $locator->findResources('files://'));

        // When listing all ressources found in `files`, we should get
        // `test.json` from Floor3 and `foo.json` from floor2. `blah.json`
        // from the Grage shoudn't be there because it's shared (?)
        /*$this->assertEquals([
            __DIR__ . '/Building/Floors/Floor3/files/test.json',
            __DIR__ . '/Building/Floors/Floor2/files/foo.json'
        ], $locator->listResources('files://'));*/

        // We also test the stream wrapper works
        /*$path = $locator->findResource('files://test.json');
        $swContent = file_get_contents('files://test.json');
        $pathContent = file_get_contents($path);
        $this->assertEquals($swContent, $pathContent);*/
    }

    /**
     * To be backward compatible with older version of UserFrosting (less than 4.2)
     * and avoid introduciton a breaking change, this package should be compatible
     * with RocketTheme UniformResourceLocator. We test this here
     *
     * @param  ResourceLocator $locator Our locator
     */
    protected function rocketThemeUniformResourceLocatorCompatibility(ResourceLocator $locator)
    {
        // Setup old locator
        $toolBox = new \RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator(__DIR__ . '/Building');
        $toolBox->addPath('cars', '', 'Garage/cars');
        $toolBox->addPath('files', '', 'Floors/Floor/files');
        $toolBox->addPath('files', '', 'Floors/Floor2/files');
        $toolBox->addPath('files', '', 'Floors/Floor3/files');
        /*\RocketTheme\Toolbox\StreamWrapper\ReadOnlyStream::setLocator($toolBox);

        $streams = [
            'cars' => '\\RocketTheme\\Toolbox\\StreamWrapper\\ReadOnlyStream',
            'files' => '\\RocketTheme\\Toolbox\\StreamWrapper\\ReadOnlyStream'
        ];

        // Before registering them, we need to unregister any that where previously registered.
        // This will cause error when two scripts are run in succession from the CLI
        foreach ($streams as $scheme => $handler) {
            if (in_array($scheme, stream_get_wrappers())) {
                stream_wrapper_unregister($scheme);
            }
        }

        $sb = new \RocketTheme\Toolbox\StreamWrapper\StreamBuilder($streams);*/

        $this->assertEquals(
            $toolBox->findResource('cars://cars.json'),
            $locator->findResource('cars://cars.json')
        );

        $this->assertEquals(
            $toolBox->findResources('cars://cars.json'),
            $locator->findResources('cars://cars.json')
        );

        $this->assertEquals(
            $toolBox->findResource('files://test.json'),
            $locator->findResource('files://test.json')
        );

        $this->assertEquals(
            $toolBox->findResources('files://test.json'),
            $locator->findResources('files://test.json')
        );
    }
}