<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator\Tests;

use PHPUnit\Framework\TestCase;
use RocketTheme\Toolbox\ResourceLocator\ResourceLocatorInterface;
use UserFrosting\UniformResourceLocator\Resource;
use UserFrosting\UniformResourceLocator\ResourceLocation;
use UserFrosting\UniformResourceLocator\ResourceLocator;
use UserFrosting\UniformResourceLocator\ResourceStream;

/**
 * Tests for ResourceLocator
 */
class BuildingLocatorTest extends TestCase
{
    /** @var string $basePath **/
    protected $basePath = __DIR__ . '/Building/';

    /** @var ResourceLocatorInterface **/
    static protected $locator;

    /**
     * Setup shared locator for resources tests
     *
     * This will setup the following streams:
     *      cars://     -> A Shared stream, loading from Building/Garage/cars, not subject to locations
     *      files://    -> Returning all files from `Building/Floors/{floorX}/files` as well as `Building/upload/data/files/`
     *      conf://     -> Returning all files from `Building/Floors/{floorX}/config` only
     *
     * Locations are : Floor1, Floor2 & Floor3
     * This means Floor 3 as top priority, and will be searched first
     *
     * Test file structure :
     *  Floor1  ->  files/test/blah.json
     *          ->  files/test.json
     *  Floor2  ->  config/test.json
     *          ->  files/data/foo.json
     *          ->  files/foo.json
     *          ->  files/test.json
     *  Floor3  ->  cars/cars.json
     *          ->  files/test.json
     *  Garage  ->  cars/cars.json
     *          ->  files/blah.json
     *  upload  ->  data/files/foo.json
     *
     * So, files found for each stream should be, when looking only at the top most :
     *      cars://
     *          - Garage/cars/cars.json
     *      files://
     *          - Floors/Floor3/files/test.json
     *          - Floors/Floor2/files/foo.json
     *          - upload/data/files/foo.json (as data/foo.json using prefix)
     *          - Floors/Floor/files/test/blah.json
     *      conf://
     *          - Floors/Floor2/config/test.json
     *
     * The following files are purely as placeholder, and should never be found :
     *  - Floors/Floor3/cars/cars.json : Should never be returned when listing cars, because the floors are not part of the cars:// search path
     *  - Floors/Floor2/test.json : Overwritten by Floor3 version
     *  - Floors/Floor1/test.json : Overwritten by Floor3 version
     *  - Garage/files/blah.json : Should never be found, because the Garage is not part of the file:// search path
     *
     * Finally, `upload/data/files/foo.json` will be
     *
     */
    public function setUp()
    {
        parent::setup();

        self::$locator = new ResourceLocator($this->basePath);

        // Register the floors.
        // Note the missing `/` at the end for Floor 3 and the one before Floor2. This shound't make any difference.
        // But at the beggining should produce error, because it produce an absolute path. We're not testing with absolute paths
        self::$locator->registerLocation('Floor1', 'Floors/Floor/');
        self::$locator->registerLocation('Floor2', '/Floors/Floor2/');
        self::$locator->registerLocation('Floor3', 'Floors/Floor3');

        // Register the streams
        self::$locator->registerStream('files');                                               // Search path -> Building/Floors/{floorX}/file (normal stream)
        self::$locator->registerStream('files', 'data', 'upload/data/files', true);            // Search path -> Building/upload/data/files/ (Stream with prefix + shared)
        self::$locator->registerStream('conf', '', 'config');                                  // Search path -> Building/Floors/{floorX}/config (stream where scheme != path)
        self::$locator->registerStream('cars', '', 'Garage/cars/', true);                      // Search path -> Building/Garage/cars (Stream shared, no prefix)
        self::$locator->registerStream('absCars', '', $this->basePath . 'Garage/cars/', true); // Search path -> Building/Garage/cars (Stream shared, no prefix, using absolute path)
    }

    /**
     * @dataProvider findProvider
     * @param  string $scheme
     * @param  string $file
     * @param  bool $array
     * @param  bool $all
     * @param  array|string $expectedResult
     */
    public function testFind($scheme, $file, $array, $all, $expectedResult)
    {
        // find($scheme, $file, $array, $all)
        $resource = $this->invokeMethod(self::$locator, 'find', [$scheme, $file, $array, $all]);

        if ($array) {
            $this->assertInternalType('array', $resource);
            $this->assertEquals($expectedResult, $resource);
        } else {
            $this->assertInstanceOf(Resource::class, $resource);
            $this->assertEquals($expectedResult, $resource->getAbsolutePath());
        }
    }

    /**
     * DataProvider for testFind
     * Return all files available from our test case
     */
    public function findProvider()
    {
        return [
            //[$scheme, $file, $array, $all, $expectedResult],
            ['cars', 'cars.json', false, false, $this->basePath . 'Garage/cars/cars.json'],
            ['cars', 'cars.json', true, false, [$this->basePath . 'Garage/cars/cars.json']],

            //['absCars', 'cars.json', false, false, $this->basePath . 'Garage/cars/cars.json'],
            //['absCars', 'cars.json', true, false, [$this->basePath . 'Garage/cars/cars.json']],

            ['files', 'test.json', false, false, $this->basePath . 'Floors/Floor3/files/test.json'],
            ['files', 'test.json', true, false, [
                $this->basePath . 'Floors/Floor3/files/test.json',
                $this->basePath . 'Floors/Floor2/files/test.json',
                $this->basePath . 'Floors/Floor/files/test.json',
            ]],

            ['files', 'foo.json', false, false, $this->basePath . 'Floors/Floor2/files/foo.json'],
            ['files', 'foo.json', true, false, [$this->basePath . 'Floors/Floor2/files/foo.json']],

            ['files', 'data/foo.json', false, false, $this->basePath . 'upload/data/files/foo.json'],
            ['files', 'data/foo.json', true, false, [
                $this->basePath . 'upload/data/files/foo.json',
                $this->basePath . 'Floors/Floor2/files/data/foo.json'
            ]],

            ['files', 'test/blah.json', false, false, $this->basePath . 'Floors/Floor/files/test/blah.json'],
            ['files', 'test/blah.json', true, false, [$this->basePath . 'Floors/Floor/files/test/blah.json']],

            ['conf', 'test.json', false, false, $this->basePath . 'Floors/Floor2/config/test.json'],
            ['conf', 'test.json', true, false, [$this->basePath . 'Floors/Floor2/config/test.json']],

        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFindThrowExceptionWhenSchemaDontExist()
    {
        $this->invokeMethod(self::$locator, 'find', ['foo', 'foo', false, false]);
    }

    /**
     * Call protected/private method of a class.
     *
     * @param  object &$object    Instantiated object that we will run method on.
     * @param  string $methodName Method name to call
     * @param  array  $parameters Array of parameters to pass into method.
     * @return mixed  Method return.
     */
    protected function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetResourceThrowExceptionIfShemeNotExist()
    {
        self::$locator->getResource('foo://');
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testGetResourceThrowExceptionOnInvalidParameterUri()
    {
        self::$locator->getResource(123);
    }

    /**
     * @dataProvider sharedResourceProvider
     * @param string $uri
     * @param string $path
     */
    public function testGetResourceForSharedStream($uri, $path)
    {
        $locator = self::$locator;

        $resource = $locator->getResource($uri);
        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertEquals($this->basePath . $path, $resource);
        $this->assertEquals($path, $resource->getPath());
        $this->assertNull($resource->getLocation());
        $this->assertEquals($uri, $resource->getUri());
        $this->assertInstanceOf(ResourceStream::class, $resource->getStream());
    }

    /**
     */
    public function testGetResourceForSharedStreamReturnFalseIfNoResourceFalse()
    {
        $locator = self::$locator;

        $resource = $locator->getResource('cars://idontExist.txt');
        $this->assertNotInstanceOf(Resource::class, $resource);
        $this->assertFalse(false, $resource);
    }

    /**
     * @dataProvider sharedResourceProvider
     * @param string $uri
     * @param string $path
     */
    public function testGetResourcesForSharedStream($uri, $path)
    {
        $locator = self::$locator;

        $resources = $locator->getResources($uri);
        $this->assertInternalType('array', $resources);
        $this->assertCount(1, $resources);
        $this->assertInstanceOf(Resource::class, $resources[0]);
        $this->assertEquals($this->basePath . $path, $resources[0]);
        $this->assertEquals($uri, $resources[0]->getUri());
    }

    /**
     */
    public function testGetResourcesForSharedStreamReturnFalseIfNoResourceFalse()
    {
        $locator = self::$locator;

        $resources = $locator->getResources('cars://idontExist.txt');
        $this->assertInternalType('array', $resources);
        $this->assertCount(0, $resources);
    }

    /**
     * @dataProvider sharedResourceProvider
     * @param string $uri
     * @param string $path
     */
    public function testFindResourceForSharedStream($uri, $path)
    {
        $locator = self::$locator;

        // Same tests, for `__invoke`, findResource` & `findResources`
        $this->assertEquals($this->basePath . $path, $locator($uri));
        $this->assertEquals($this->basePath . $path, $locator->findResource($uri));
        $this->assertEquals([$this->basePath . $path], $locator->findResources($uri));

        // Expect same result with relative paths
        $this->assertEquals($path, $locator->findResource($uri, false));
        $this->assertEquals([$path], $locator->findResources($uri, false));
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testFindResourceThrowExceptionOnBadUri()
    {
        self::$locator->findResource(123);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testFindResourcesThrowExceptionOnBadUri()
    {
        self::$locator->findResources(123);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testInvokeThrowExceptionOnBadUri()
    {
        $locator = self::$locator;
        $locator(123);
    }

    /**
     */
    public function testFindResourceForSharedStreamReturnFalseIfNoResourceFalse()
    {
        $locator = self::$locator;
        $uri = 'cars://idontExist.txt';

        // Same tests, for `__invoke`, `findResource` & `findResources`
        $this->assertEquals(false, $locator($uri));
        $this->assertEquals(false, $locator->findResource($uri));
        $this->assertEquals([], $locator->findResources($uri));

        // Expect same result with relative paths
        $this->assertEquals(false, $locator->findResource($uri, false));
        $this->assertEquals([], $locator->findResources($uri, false));
    }

    /**
     * Data provider for shared stream tests
     */
    public function sharedResourceProvider()
    {
        return [
            ['cars://cars.json', 'Garage/cars/cars.json'],
            //['cars://', 'Garage/cars'],
            //['absCars://cars.json', 'Garage/cars/cars.json'],
        ];
    }

    /**
     * Data provider for normal stream test
     */
    public function resourceProvider()
    {
        return [
            //uri, path
            ['files://test.json', 'Floors/Floor3/files/test.json'],
            ['files://foo.json', 'Floors/Floor2/files/foo.json'],
            ['files://data/foo.json', 'Floors/Floor2/files/data/foo.json'],
            ['files://test/blah.json', 'Floors/Floor3/files/test/blah.json'],
            ['files://', 'Floors/Floor3/files/'],
        ];
    }

    /**
     */
    public function testListResourcesForSharedStream()
    {
        $list = self::$locator->listResources('cars://');
        $this->assertCount(1, $list);
        $this->assertEquals([
            $this->basePath . 'Garage/cars/cars.json'
        ], array_map('strval', $list));
    }

    /**
     */
    public function testListResourcesForSharedStreamWithAllArgument()
    {
        $list = self::$locator->listResources('cars://', true);
        $this->assertCount(1, $list);
        $this->assertEquals([
            $this->basePath . 'Garage/cars/cars.json'
        ], array_map('strval', $list));
    }

    /**
     * In this test, `Floors/Floor2/files/data/foo.json` is not returned,
     * because we don't list recursively
     */
    public function testListResourcesForFiles()
    {
        $list = self::$locator->listResources('files://');
        $this->assertCount(3, $list);
        $this->assertEquals([
            $this->basePath . 'Floors/Floor/files/test/blah.json',
            $this->basePath . 'Floors/Floor2/files/foo.json',
            $this->basePath . 'Floors/Floor3/files/test.json'
        ], array_map('strval', $list));
    }

    /**
     * List all ressources under listResources
     */
    public function testListResourcesForFilesWithAllArgument()
    {
        $list = self::$locator->listResources('files://', true);
        $this->assertCount(6, $list);
        $this->assertEquals([
            $this->basePath . 'Floors/Floor/files/test.json',
            $this->basePath . 'Floors/Floor/files/test/blah.json',
            $this->basePath . 'Floors/Floor2/files/data/foo.json',
            $this->basePath . 'Floors/Floor2/files/foo.json',
            $this->basePath . 'Floors/Floor2/files/test.json',
            $this->basePath . 'Floors/Floor3/files/test.json',
        ], array_map('strval', $list));
    }

    /**
     * upload file will be showed here, as we're in the data prefix
     */
    public function testListResourcesForDataFilesWithAllArgument()
    {
        $list = self::$locator->listResources('files://data', true);
        $this->assertCount(2, $list);
        $this->assertEquals([
            $this->basePath . 'Floors/Floor2/files/data/foo.json',
            $this->basePath . 'upload/data/files/foo.json',
        ], array_map('strval', $list));
    }
}
