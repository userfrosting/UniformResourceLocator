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
class BuildingLocatorTest extends TestCase
{
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
    }

    /**
     * Subtest for getResources
     * @param ResourceLocator $locator
     *
     * @depends testBuildingLocator
     */
    public function testSharedStreamTest_getResources(ResourceLocator $locator)
    {
        // Find same result with many ressources
        $resources = $locator->getResources('cars://cars.json');
        $this->assertInternalType('array', $resources);
        $this->assertInstanceOf(Resource::class, $resources[0]);
        $this->assertEquals(__DIR__ . '/Building/Garage/cars/cars.json', $resources[0]);
        $this->assertEquals('cars://cars.json', $resources[0]->getUri());

        // Same tests, for `findResource` & `findResources`
        $this->assertEquals(__DIR__ . '/Building/Garage/cars/cars.json', $locator->findResource('cars://cars.json'));
        $this->assertEquals([__DIR__ . '/Building/Garage/cars/cars.json'], $locator->findResources('cars://cars.json'));
    }

    /**
     * Subtest for getResource
     * @param ResourceLocator $locator
     *
     * @depends testBuildingLocator
     */
    public function testSharedStreamTest_getResource(ResourceLocator $locator)
    {
        // Should also work with simple stream (non file uri)
        $resource = $locator->getResource('cars://');
        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertEquals(__DIR__ . '/Building/Garage/cars', $resource);
        $this->assertEquals('Garage/cars', $resource->getRelPath());
        $this->assertNull($resource->getLocation());

        // Same tests, for `findResource` & `findResources`
        $this->assertEquals(__DIR__ . '/Building/Garage/cars', $locator->findResource('cars://'));
        $this->assertEquals([__DIR__ . '/Building/Garage/cars'], $locator->findResources('cars://'));
    }

    /**
     * Subtest for listResources
     * @param ResourceLocator $locator
     *
     * @depends testBuildingLocator
     */
    public function testSharedStreamTest_listResources(ResourceLocator $locator)
    {
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
     * Subtest for listResources with all results
     * @param ResourceLocator $locator
     *
     * @depends testBuildingLocator
     */
    public function testSharedStreamTest_listAllResources(ResourceLocator $locator)
    {
        $list = $locator->listResources('cars://', true);
        $this->assertCount(1, $list);
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

        return $locator;
    }

    /**
     * Find same result with many ressources
     *
     * @param ResourceLocator $locator
     * @depends testNormalStreamTest
     */
    public function testNormalStreamTest_getResources(ResourceLocator $locator)
    {
        $resources = $locator->getResources('files://test.json');
        $this->assertInternalType('array', $resources);
        $resource = $resources[1];
        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertEquals(__DIR__ . '/Building/Floors/Floor2/files/test.json', $resource);
        $this->assertEquals('Floors/Floor2/files/test.json', $resource->getRelPath());
        $this->assertEquals('files://test.json', $resource->getUri());
        $this->assertEquals('Floor2', $resource->getLocation()->getName());
        $this->assertEquals('Floors/Floor2', $resource->getLocation()->getPath());
    }

    /**
     * Same tests for `findResource` & `findResources`
     *
     * @param ResourceLocator $locator
     * @depends testNormalStreamTest
     */
    public function testNormalStreamTest_findResource(ResourceLocator $locator)
    {
        $this->assertEquals(__DIR__ . '/Building/Floors/Floor3/files/test.json', $locator->findResource('files://test.json'));
        $this->assertEquals([
            __DIR__ . '/Building/Floors/Floor3/files/test.json',
            __DIR__ . '/Building/Floors/Floor2/files/test.json',
            __DIR__ . '/Building/Floors/Floor/files/test.json'
        ], $locator->findResources('files://test.json'));
    }

    /**
     * Should also work with simple path (non file uri)
     *
     * @param ResourceLocator $locator
     * @depends testNormalStreamTest
     */
    public function testNormalStreamTest_simplePath(ResourceLocator $locator)
    {
        $this->assertEquals(__DIR__ . '/Building/Floors/Floor3/files', $locator->findResource('files://'));
        $this->assertEquals([
            __DIR__ . '/Building/Floors/Floor3/files',
            __DIR__ . '/Building/Floors/Floor2/files',
            __DIR__ . '/Building/Floors/Floor/files'
        ], $locator->findResources('files://'));
    }

    /**
     * When listing all ressources found in `files`, we should get
     * `test.json` from Floor3 and `foo.json` from floor2. `blah.json`
     * from the Garage shoudn't be there because it's shared
     *
     * @param ResourceLocator $locator
     * @depends testNormalStreamTest
     */
    public function testNormalStreamTest_listResources(ResourceLocator $locator)
    {
        $list = $locator->listResources('files://');
        $this->assertCount(3, $list);
        $this->assertEquals([
            __DIR__ . '/Building/Floors/Floor/files/test/blah.json',
            __DIR__ . '/Building/Floors/Floor2/files/foo.json',
            __DIR__ . '/Building/Floors/Floor3/files/test.json'
        ], array_map('strval', $list));
        $this->assertInstanceOf(Resource::class, $list[0]);
        $this->assertEquals('Floor3', $list[2]->getLocation()->getName());
        $this->assertEquals('Floors/Floor3/files/test.json', $list[2]->getRelPath());
        $this->assertEquals('files://test.json', $list[2]->getUri());
    }

    /**
     * Same test, but listing all ressources. So same thing, everyting in the
     * floors, but nothing from the garage
     *
     * @param ResourceLocator $locator
     * @depends testNormalStreamTest
     */
    public function testNormalStreamTest_listAllResources(ResourceLocator $locator)
    {
        $list = $locator->listResources('files://', true);
        $this->assertCount(6, $list);
        $this->assertEquals([
            __DIR__ . '/Building/Floors/Floor/files/test.json',
            __DIR__ . '/Building/Floors/Floor/files/test/blah.json',
            __DIR__ . '/Building/Floors/Floor2/files/data/foo.json',
            __DIR__ . '/Building/Floors/Floor2/files/foo.json',
            __DIR__ . '/Building/Floors/Floor2/files/test.json',
            __DIR__ . '/Building/Floors/Floor3/files/test.json',
        ], array_map('strval', $list));
    }

    /**
     * findResources & listResources should work fine with the prefix
     *
     * @param ResourceLocator $locator
     * @depends testNormalStreamTest
     */
    public function testNormalStreamTest_withPrefix(ResourceLocator $locator)
    {
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

        // When listing all ressources, we will see both `foo.json` files
        $list = $locator->listResources('files://data', true);
        $this->assertCount(2, $list);
        $this->assertEquals([
            __DIR__ . '/Building/Floors/Floor2/files/data/foo.json',
            __DIR__ . '/Building/upload/data/files/foo.json'
        ], array_map('strval', $list));
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

    /**
     * Test registerStream
     */
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
