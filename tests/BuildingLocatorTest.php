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
use UserFrosting\UniformResourceLocator\ResourceLocator;
use UserFrosting\UniformResourceLocator\ResourceStream;

/**
 * Tests for ResourceLocator
 */
class BuildingLocatorTest extends TestCase
{
    /** @var string $basePath */
    protected $basePath = __DIR__ . '/Building/';

    /** @var ResourceLocatorInterface */
    protected static $locator;

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
     */
    public function setUp()
    {
        parent::setup();

        self::$locator = new ResourceLocator($this->basePath);

        // Register the floors.
        // Note the missing `/` at the end for Floor 3. This shound't make any difference.
        // At the beginning, it means the locator use an absolute path, bypassing Locator base path for that locator
        // Floor2 simulate an absolute path for that location. Note it won't make any sense (and fail) if both
        // the location and the stream uses absolute paths
        self::$locator->registerLocation('Floor1', 'Floors/Floor/');
        self::$locator->registerLocation('Floor2', $this->basePath . 'Floors/Floor2/');
        self::$locator->registerLocation('Floor3', 'Floors/Floor3');

        // Register the streams
        self::$locator->registerStream('files');                                               // Search path -> Building/Floors/{floorX}/file (normal stream)
        self::$locator->registerStream('files', 'data', 'upload/data/files', true);            // Search path -> Building/upload/data/files/ (Stream with prefix + shared)
        self::$locator->registerStream('conf', '', 'config');                                  // Search path -> Building/Floors/{floorX}/config (stream where scheme != path)
        self::$locator->registerStream('cars', '', 'Garage/cars/', true);                      // Search path -> Building/Garage/cars (Stream shared, no prefix)
        self::$locator->registerStream('absCars', '', $this->basePath . 'Garage/cars/', true); // Search path -> Building/Garage/cars (Stream shared, no prefix, using absolute path)
    }

    /**
     * @dataProvider resourceProvider
     * @dataProvider sharedResourceProvider
     * @param string       $scheme
     * @param string       $file
     * @param string|null  $location
     * @param array|string $expectedPaths
     */
    public function testFind($scheme, $file, $location, $expectedPaths)
    {
        // find($scheme, $file, $array, $all)
        $resource = $this->invokeMethod(self::$locator, 'find', [$scheme, $file, false, false]);

        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertEquals($this->basePath . $expectedPaths[0], $resource->getAbsolutePath());
    }

    /**
     * @dataProvider resourceProvider
     * @dataProvider sharedResourceProvider
     * @depends testFind
     * @param string       $scheme
     * @param string       $file
     * @param string|null  $location
     * @param array|string $expectedPaths
     */
    public function testFindWithArray($scheme, $file, $location, $expectedPaths)
    {
        // find($scheme, $file, $array, $all)
        $resource = $this->invokeMethod(self::$locator, 'find', [$scheme, $file, true, false]);

        $this->assertInternalType('array', $resource);
        $this->assertEquals($this->relativeToAbsolutePaths($expectedPaths), $resource);
    }

    /**
     * @dataProvider resourceProvider
     * @dataProvider sharedResourceProvider
     * @depends testFind
     * @param string       $scheme
     * @param string       $file
     * @param string|null  $location
     * @param array|string $expectedPaths
     * @param array|string $expectedAllPaths
     */
    public function testFindWithAll($scheme, $file, $location, $expectedPaths, $expectedAllPaths)
    {
        // find($scheme, $file, $array, $all)
        $resource = $this->invokeMethod(self::$locator, 'find', [$scheme, $file, false, true]);

        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertEquals($this->basePath . $expectedAllPaths[0], $resource->getAbsolutePath());
    }

    /**
     * @dataProvider resourceProvider
     * @dataProvider sharedResourceProvider
     * @depends testFind
     * @param string       $scheme
     * @param string       $file
     * @param string|null  $location
     * @param array|string $expectedPaths
     * @param array|string $expectedAllPaths
     */
    public function testFindWithArrayAndAll($scheme, $file, $location, $expectedPaths, $expectedAllPaths)
    {
        // find($scheme, $file, $array, $all)
        $resource = $this->invokeMethod(self::$locator, 'find', [$scheme, $file, true, true]);

        $this->assertInternalType('array', $resource);
        $this->assertEquals($this->relativeToAbsolutePaths($expectedAllPaths), $resource);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @depends testFind
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
     * @param string       $scheme
     * @param string       $file
     * @param string|null  $location
     * @param array|string $expectedPaths
     */
    public function testGetResourceForSharedStream($scheme, $file, $location, $expectedPaths)
    {
        $locator = self::$locator;
        $uri = $scheme . '://' . $file;

        $resource = $locator->getResource($uri);
        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertEquals($this->basePath . $expectedPaths[0], $resource);
        $this->assertEquals($expectedPaths[0], $resource->getPath());
        $this->assertNull($resource->getLocation());
        $this->assertEquals($uri, $resource->getUri());
        $this->assertInstanceOf(ResourceStream::class, $resource->getStream());
    }

    /**
     * @depends testGetResourceForSharedStream
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
     * @depends testGetResourceForSharedStream
     * @param string       $scheme
     * @param string       $file
     * @param string|null  $location
     * @param array|string $expectedPaths
     */
    public function testGetResourcesForSharedStream($scheme, $file, $location, $expectedPaths)
    {
        $locator = self::$locator;
        $uri = $scheme . '://' . $file;

        $resources = $locator->getResources($uri);
        $this->assertInternalType('array', $resources);
        $this->assertCount(count($expectedPaths), $resources);
        $this->assertInstanceOf(Resource::class, $resources[0]);
        $this->assertEquals($this->basePath . $expectedPaths[0], $resources[0]);
        $this->assertEquals($uri, $resources[0]->getUri());
    }

    /**
     * @depends testGetResourcesForSharedStream
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
     * @depends testGetResourceForSharedStream
     * @depends testGetResourcesForSharedStream
     * @param string       $scheme
     * @param string       $file
     * @param string|null  $location
     * @param array|string $expectedPaths
     */
    public function testFindResourceForSharedStream($scheme, $file, $location, $expectedPaths)
    {
        $locator = self::$locator;
        $uri = $scheme . '://' . $file;

        // Same tests, for `__invoke`, findResource` & `findResources`
        $this->assertEquals($this->basePath . $expectedPaths[0], $locator($uri));
        $this->assertEquals($this->basePath . $expectedPaths[0], $locator->findResource($uri));
        $this->assertEquals([$this->basePath . $expectedPaths[0]], $locator->findResources($uri));

        // Expect same result with relative paths
        $this->assertEquals($expectedPaths[0], $locator->findResource($uri, false));
        $this->assertEquals($expectedPaths, $locator->findResources($uri, false));
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
     * @dataProvider resourceProvider
     * @param string       $scheme
     * @param string       $file
     * @param string|null  $location
     * @param array|string $expectedPaths
     */
    public function testGetResource($scheme, $file, $location, $expectedPaths)
    {
        $locator = self::$locator;
        $uri = $scheme . '://' . $file;

        $resource = $locator->getResource($uri);
        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertEquals($this->basePath . $expectedPaths[0], $resource);
        $this->assertEquals($expectedPaths[0], $resource->getPath());
        $this->assertEquals($uri, $resource->getUri());
        $this->assertInstanceOf(ResourceStream::class, $resource->getStream());

        if (is_null($location)) {
            $this->assertNull($resource->getLocation());
        } else {
            $this->assertEquals($location, $resource->getLocation()->getName());
        }
    }

    /**
     * @dataProvider resourceProvider
     * @depends testGetResource
     * @param string       $scheme
     * @param string       $file
     * @param string|null  $location
     * @param array|string $expectedPaths
     */
    public function testGetResources($scheme, $file, $location, $expectedPaths)
    {
        $locator = self::$locator;
        $uri = $scheme . '://' . $file;

        $resources = $locator->getResources($uri);
        $this->assertInternalType('array', $resources);
        $this->assertCount(count($expectedPaths), $resources);
        $this->assertEquals($this->relativeToAbsolutePaths($expectedPaths), $resources);
        $this->assertInstanceOf(Resource::class, $resources[0]);
        $this->assertEquals($this->basePath . $expectedPaths[0], $resources[0]);
        $this->assertEquals($uri, $resources[0]->getUri());
    }

    /**
     * @dataProvider resourceProvider
     * @depends testGetResource
     * @depends testGetResources
     * @param string       $scheme
     * @param string       $file
     * @param string|null  $location
     * @param array|string $expectedPaths
     */
    public function testFindResource($scheme, $file, $location, $expectedPaths)
    {
        $locator = self::$locator;
        $uri = $scheme . '://' . $file;

        // Same tests, for `__invoke`, findResource` & `findResources`
        $this->assertEquals($this->basePath . $expectedPaths[0], $locator($uri));
        $this->assertEquals($this->basePath . $expectedPaths[0], $locator->findResource($uri));
        $this->assertEquals($this->relativeToAbsolutePaths($expectedPaths), $locator->findResources($uri));

        // Expect same result with relative paths
        $this->assertEquals($expectedPaths[0], $locator->findResource($uri, false));
        $this->assertEquals($expectedPaths, $locator->findResources($uri, false));
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
     * @depends testListResourcesForSharedStream
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
     * @depends testListResourcesForFiles
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
     * @depends testListResourcesForFilesWithAllArgument
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

    /**
     * DataProvider for testFind
     * Return all files available from our test case
     */
    public function resourceProvider()
    {
        return [
            //[$scheme, $file, $location, $expectedPaths, $expectedAllPaths],
            // #0
            ['files', 'test.json', 'Floor3', [
                'Floors/Floor3/files/test.json',
                'Floors/Floor2/files/test.json',
                'Floors/Floor/files/test.json',
            ], [
                'Floors/Floor3/files/test.json',
                'Floors/Floor2/files/test.json',
                'Floors/Floor/files/test.json',
            ]],

            // #1
            ['files', 'foo.json', 'Floor2', [
                'Floors/Floor2/files/foo.json'
            ], [
                'Floors/Floor3/files/foo.json',
                'Floors/Floor2/files/foo.json',
                'Floors/Floor/files/foo.json',
            ]],

            // #2
            ['files', 'data/foo.json', null, [
                'upload/data/files/foo.json',
                'Floors/Floor2/files/data/foo.json'
            ], [
                'upload/data/files/foo.json',
                'Floors/Floor3/files/data/foo.json',
                'Floors/Floor2/files/data/foo.json',
                'Floors/Floor/files/data/foo.json'
            ]],

            // #3
            ['files', 'test/blah.json', 'Floor1', [
                'Floors/Floor/files/test/blah.json'
            ], [
                'Floors/Floor3/files/test/blah.json',
                'Floors/Floor2/files/test/blah.json',
                'Floors/Floor/files/test/blah.json'
            ]],

            // #4
            // N.B.: upload/data/files is not returned here as the `data` prefix is not used
            ['files', '', 'Floor3', [
                'Floors/Floor3/files',
                'Floors/Floor2/files',
                'Floors/Floor/files',
            ], [
                'Floors/Floor3/files',
                'Floors/Floor2/files',
                'Floors/Floor/files',
            ]],

            // #5
            // Test the data prefix here
            ['files', 'data', null, [
                'upload/data/files',
                'Floors/Floor2/files/data',
            ], [
                'upload/data/files',
                'Floors/Floor3/files/data',
                'Floors/Floor2/files/data',
                'Floors/Floor/files/data'
            ]],

            // #6
            ['conf', 'test.json', 'Floor2', [
                'Floors/Floor2/config/test.json'
            ], [
                'Floors/Floor3/config/test.json',
                'Floors/Floor2/config/test.json',
                'Floors/Floor/config/test.json'
            ]],
        ];
    }

    /**
     * Data provider for shared stream tests
     */
    public function sharedResourceProvider()
    {
        return [
            //[$scheme, $file, $lcoation, $expectedPaths, $expectedAllPaths],
            // #0
            ['cars', 'cars.json', null, [
                'Garage/cars/cars.json'
            ], [
                'Garage/cars/cars.json'
            ]],

            // #1
            ['cars', '', null, [
                'Garage/cars'
            ], [
                'Garage/cars'
            ]],

            // #2
            ['absCars', 'cars.json', null, [
                'Garage/cars/cars.json'
            ], [
                'Garage/cars/cars.json'
            ]],
        ];
    }

    /**
     * Convert an array of relative paths to absolute paths
     * @param  array $paths relative paths
     * @return array absolute paths
     */
    protected function relativeToAbsolutePaths(array $paths)
    {
        $pathsWithAbsolute = [];
        foreach ($paths as $p) {
            $pathsWithAbsolute[] = $this->basePath . $p;
        }

        return $pathsWithAbsolute;
    }

    /**
     */
    public function testFindCachedReturnFalseOnBadUriPart()
    {
        $locator = new ResourceLocator();
        $resource = $locator->getResource('path/to/../../../file.txt');
        $this->assertFalse($resource);
    }

    /**
     */
    public function testFindCachedReturnFalseOnBadUriPartWithArray()
    {
        $locator = new ResourceLocator();
        $resources = $locator->getResources('path/to/../../../file.txt');
        $this->assertSame([], $resources);
    }
}
