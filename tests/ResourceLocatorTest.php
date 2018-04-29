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
     * Test ResourceStream Class
     */
    public function testResourceStream()
    {
        // Test instance & default values
        $stream = new ResourceStream('');
        $this->assertInstanceOf(ResourceStream::Class, $stream);
        $this->assertEquals('', $stream->getScheme());
        $this->assertEquals('', $stream->getPath());
        $this->assertFalse($stream->isShared());

        // Set/get scheme, path and shared properties
        $stream->setScheme('foo');
        $this->assertEquals('foo', $stream->getScheme());

        $stream->setPath('/bar');
        $this->assertEquals('/bar', $stream->getPath());

        $stream->setShared(true);
        $this->assertTrue($stream->isShared());

        // Now try again with the info in the constructor
        $stream = new ResourceStream('bar', '', '/foo', true);
        $this->assertEquals('bar', $stream->getScheme());
        $this->assertEquals('/foo', $stream->getPath());
        $this->assertTrue($stream->isShared());

        // When no path is defined, the name should be used
        $stream = new ResourceStream('etc');
        $this->assertEquals('etc', $stream->getScheme());
        $this->assertEquals('etc', $stream->getPath());
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


        // Test stream manipulation...
        $stream = new ResourceStream('bar', '', '/foo');
        $locator->addStream($stream);
        $locator->registerStream('foo', '', '/bar');

        // ...getStream
        $barStream = $locator->getStream('bar');
        $this->assertInternalType('array', $barStream);
        $this->assertInstanceOf(ResourceStream::class, $barStream[''][0]);
        $this->assertEquals('/foo', $barStream[''][0]->getPath());

        // ...getStreams
        $streams = $locator->getStreams();
        $this->assertInternalType('array', $streams);
        $this->assertCount(2, $streams);
        $this->assertInstanceOf(ResourceStream::class, $streams['bar'][''][0]);
        $this->assertEquals('/foo', $streams['bar'][''][0]->getPath());

        // ...listStreams
        $this->assertEquals(['bar', 'foo'], $locator->listStreams());

        // ...removeStream
        $locator->removeStream('bar');
        $this->assertCount(1, $locator->getStreams());

        // ...schemeExist
        $this->assertTrue($locator->schemeExist('foo'));
        $this->assertFalse($locator->schemeExist('bar'));
        $this->assertFalse($locator->schemeExist('etc'));

        // ...isStream
        $this->assertFalse($locator->isStream('cars://foo'));
        $this->assertTrue($locator->isStream('foo://cars'));


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

        // ...locationExist
        $this->assertTrue($locator->locationExist('foo'));
        $this->assertFalse($locator->locationExist('bar'));
        $this->assertFalse($locator->locationExist('etc'));


        // Test reset
        $locator->reset();
        $this->assertCount(0, $locator->getStreams());
        $this->assertCount(0, $locator->getLocations());
    }

    /**
     * Test StreamNotFoundException
     * @expectedException \UserFrosting\UniformResourceLocator\Exception\StreamNotFoundException
     */
    public function testStreamNotFoundException()
    {
        $locator = new ResourceLocator;
        $locator->getStream('etc');
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

        // Register the floors.
        // Note the missing `/` at the end. This shound't make any difference.
        // But at the beggining should produce error, because it produce an absolute path
        $locator->registerLocation('Floor1', 'Floors/Floor/');
        $locator->registerLocation('Floor2', 'Floors/Floor2/');
        $locator->registerLocation('Floor3', 'Floors/Floor3');

        // Register the streams
        // Again, use different `/` at the end
        $locator->registerStream('files'); // Search path -> Building/Floors/{floorX}/file
        $locator->registerStream('files', 'data', 'upload/data/files', true); // Search path -> Building/upload/data/files/
        $locator->registerStream('conf', '', 'config'); // Search path -> Building/Floors/{floorX}/config
        $locator->registerStream('cars', '', 'Garage/cars/', true); // Search path -> Building/Garage/cars

        return $locator;
    }

    /**
     * subtest for the shared stream (Garage) of the "Building" file structure
     * @param ResourceLocator $locator
     *
     * @depends testBuildingLocator
     */
    public function testSharedStreamTest(ResourceLocator $locator)
    {
        // Find the `car.json` resource. Should be from the Garage.
        $resource = $locator->getResource('cars://cars.json');
        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertEquals(__DIR__ . '/Building/Garage/cars/cars.json', $resource);
        $this->assertEquals('Garage/cars/cars.json', $resource->getRelPath());
        $this->assertNull($resource->getLocation());
        $this->assertEquals('cars://cars.json', $resource->getUri());
        $this->assertInstanceOf(ResourceStream::class, $resource->getStream());
        $this->assertEquals('cars', $resource->getStream()->getScheme());
        $this->assertEquals('Garage/cars', $resource->getStream()->getPath());

        // Find same result with many ressources
        $resources = $locator->getResources('cars://cars.json');
        $this->assertInternalType('array', $resources);
        $this->assertInstanceOf(Resource::class, $resources[0]);
        $this->assertEquals(__DIR__ . '/Building/Garage/cars/cars.json', $resources[0]);
        $this->assertEquals('cars://cars.json', $resources[0]->getUri());

        // Same tests, for `findResource` & `findResources`
        $this->assertEquals(__DIR__ . '/Building/Garage/cars/cars.json', $locator->findResource('cars://cars.json'));
        $this->assertEquals([__DIR__ . '/Building/Garage/cars/cars.json'], $locator->findResources('cars://cars.json'));

        // Should also work with simple stream (non file uri)
        $resource = $locator->getResource('cars://');
        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertEquals(__DIR__ . '/Building/Garage/cars', $resource);
        $this->assertEquals('Garage/cars', $resource->getRelPath());
        $this->assertNull($resource->getLocation());

        // Same tests, for `findResource` & `findResources`
        $this->assertEquals(__DIR__ . '/Building/Garage/cars', $locator->findResource('cars://'));
        $this->assertEquals([__DIR__ . '/Building/Garage/cars'], $locator->findResources('cars://'));

        // Listing all ressources should only list the Garage one (not the Floor3 one)
        $list = $locator->listResources('cars://');
        $this->assertCount(1, $list);
        $this->assertInstanceOf(Resource::class, $list[0]);
        $this->assertEquals(__DIR__ . '/Building/Garage/cars/cars.json', $list[0]);
        $this->assertEquals('Garage/cars/cars.json', $list[0]->getRelPath());
        $this->assertEquals('cars://cars.json', $list[0]->getUri());
        $this->assertEquals('cars.json', $list[0]->getBasePath());

        // Test resource file info getter
        $this->assertEquals('cars', $list[0]->getFilename());
        $this->assertEquals('cars.json', $list[0]->getBasename());
        $this->assertEquals('json', $list[0]->getExtension());
    }

    /**
     * subtest for the normal stream (Floors) of the "Building" file structure
     * @param ResourceLocator $locator
     *
     * @depends testBuildingLocator
     */
    public function testNormalStreamTest(ResourceLocator $locator)
    {
        // Looking for the `test.json` file.
        // The config file should never be found when looking for files
        $resource = $locator->getResource('files://test.json');
        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertEquals(__DIR__ . '/Building/Floors/Floor3/files/test.json', $resource);
        $this->assertEquals('Floors/Floor3/files/test.json', $resource->getRelPath());
        $this->assertEquals('files://test.json', $resource->getUri());
        $this->assertEquals('Floor3', $resource->getLocation()->getName());
        $this->assertEquals('Floors/Floor3', $resource->getLocation()->getPath());

        // Find same result with many ressources
        $resources = $locator->getResources('files://test.json');
        $this->assertInternalType('array', $resources);
        $resource = $resources[1];
        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertEquals(__DIR__ . '/Building/Floors/Floor2/files/test.json', $resource);
        $this->assertEquals('Floors/Floor2/files/test.json', $resource->getRelPath());
        $this->assertEquals('files://test.json', $resource->getUri());
        $this->assertEquals('Floor2', $resource->getLocation()->getName());
        $this->assertEquals('Floors/Floor2', $resource->getLocation()->getPath());

        // Same tests for `findResource` & `findResources`
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
        // from the Garage shoudn't be there because it's shared
        $list = $locator->listResources('files://');
        $this->assertCount(3, $list);
        $this->assertEquals([
            __DIR__ . '/Building/Floors/Floor3/files/test.json',
            __DIR__ . '/Building/Floors/Floor/files/test/blah.json',
            __DIR__ . '/Building/Floors/Floor2/files/foo.json'
        ], $list);
        $this->assertInstanceOf(Resource::class, $list[0]);
        $this->assertEquals('Floor3', $list[0]->getLocation()->getName());
        $this->assertEquals('Floors/Floor3/files/test.json', $list[0]->getRelPath());
        $this->assertEquals('files://test.json', $list[0]->getUri());

        // findResources & listResources should work fine with the prefix
        $this->assertEquals(__DIR__ . '/Building/upload/data/files/foo.json', $locator->findResource('files://data/foo.json'));
        $this->assertEquals([
            __DIR__ . '/Building/upload/data/files/foo.json',
            __DIR__ . '/Building/Floors/Floor2/files/data/foo.json'
        ], $locator->findResources('files://data/foo.json'));
        $list = $locator->listResources('files://data');
        $this->assertCount(1, $list);
        $this->assertEquals([__DIR__ . '/Building/upload/data/files/foo.json'], $list);
        $this->assertInstanceOf(Resource::class, $list[0]);
        $this->assertNull($list[0]->getLocation());
        $this->assertEquals('upload/data/files/foo.json', $list[0]->getRelPath());
        $this->assertEquals('files://data/foo.json', $list[0]->getUri());
        $this->assertEquals('foo.json', $list[0]->getBasePath());
    }

    /**
     * To be backward compatible with older version of UserFrosting (less than 4.2)
     * and avoid introduciton a breaking change, this package should be compatible
     * with RocketTheme UniformResourceLocator. We test this here
     *
     * @param  ResourceLocator $locator Our locator
     *
     * @depends testBuildingLocator
     */
    public function testRocketThemeUniformResourceLocatorCompatibility(ResourceLocator $locator)
    {
        // Setup old locator
        $toolBox = new \RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator(__DIR__ . '/Building');
        $toolBox->addPath('cars', '', 'Garage/cars');
        $toolBox->addPath('files', '', 'Floors/Floor/files');
        $toolBox->addPath('files', '', 'Floors/Floor2/files');
        $toolBox->addPath('files', '', 'Floors/Floor3/files');

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

    /**
     * test the stream wrapper works
     *
     * @param  ResourceLocator $locator
     *
     * @depends testBuildingLocator
     */
    public function testStreamWrapper(ResourceLocator $locator)
    {
        // Starts with `cars`
        $this->assertTrue($locator->getStreamBuilder()->isStream('cars'));

        $path = $locator->findResource('cars://cars.json');
        $swContent = file_get_contents('cars://cars.json');
        $pathContent = file_get_contents($path);
        $this->assertEquals($swContent, $pathContent);

        // Now do the other one
        $this->assertTrue($locator->getStreamBuilder()->isStream('files'));

        $path = $locator->findResource('files://test.json');
        $swContent = file_get_contents('files://test.json');
        $pathContent = file_get_contents($path);
        $this->assertEquals($swContent, $pathContent);
    }

    /**
     * Additionnal test to make sure `getBasePath` works correctly
     *
     * @param  ResourceLocator $locator Our locator
     *
     * @depends testBuildingLocator
     */
    public function testGetBasePath(ResourceLocator $locator)
    {
        $resource = $locator->getResource('files://test/blah.json');
        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertEquals('test/blah.json', $resource->getBasePath());
    }

    public function testAddPath()
    {
        $locator = new ResourceLocator(__DIR__ . '/Building');

        // Let's try doing this manually using an array of paths
        // Last path has priority
        $locator->registerStream('files', '', [
            'Floors/Floor/files',
            'Floors/Floor2/files',
            'Floors/Floor3/files'
        ], true);

        $this->assertCount(3, $locator->getStream('files')['']);

        $resource = $locator->getResource('files://test.json');
        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertEquals(__DIR__ . '/Building/Floors/Floor3/files/test.json', $resource);
    }
}