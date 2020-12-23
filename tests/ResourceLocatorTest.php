<?php

/*
 * UserFrosting Uniform Resource Locator (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @copyright Copyright (c) 2013-2019 Alexander Weissman, Louis Charette
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator\Tests;

use PHPUnit\Framework\TestCase;
use RocketTheme\Toolbox\ResourceLocator\ResourceLocatorInterface as BaseResourceLocatorInterface;
use RocketTheme\Toolbox\StreamWrapper\StreamBuilder;
use UserFrosting\UniformResourceLocator\Exception\LocationNotFoundException;
use UserFrosting\UniformResourceLocator\Exception\StreamNotFoundException;
use UserFrosting\UniformResourceLocator\ResourceLocation;
use UserFrosting\UniformResourceLocator\ResourceLocationInterface;
use UserFrosting\UniformResourceLocator\ResourceLocator;
use UserFrosting\UniformResourceLocator\ResourceLocatorInterface;
use UserFrosting\UniformResourceLocator\ResourceStream;
use UserFrosting\UniformResourceLocator\ResourceStreamInterface;

/**
 * Tests for ResourceLocator.
 */
class ResourceLocatorTest extends TestCase
{
    /**
     * Test instance & default values.
     */
    public function testConstructor()
    {
        $locator = new ResourceLocator();
        $this->assertInstanceOf(ResourceLocatorInterface::class, $locator);
        $this->assertInstanceOf(BaseResourceLocatorInterface::class, $locator);

        return $locator;
    }

    /**
     * @depends testConstructor
     */
    public function testGetStreamBuilder()
    {
        $locator = new ResourceLocator();
        $streamBuilder = $locator->getStreamBuilder();
        $this->assertInstanceOf(StreamBuilder::class, $streamBuilder);
    }

    /**
     * @depends testConstructor
     */
    public function testGetBasePathWithEmptyConstructorArgument()
    {
        $locator = new ResourceLocator();
        $this->assertEquals('', $locator->getBasePath());
    }

    /**
     * @depends testConstructor
     */
    public function testSetBasePathWithConstructorArgument()
    {
        $locator = new ResourceLocator(__DIR__.'/Building');
        $path = str_replace('\\', '/', __DIR__);
        $this->assertEquals($path.'/Building', $locator->getBasePath());
    }

    /**
     * @depends testConstructor
     */
    public function testSetBasePath()
    {
        $locator = new ResourceLocator();
        $locator->setBasePath(__DIR__.'/Building');
        $path = str_replace('\\', '/', __DIR__);
        $this->assertEquals($path.'/Building', $locator->getBasePath());
    }

    /**
     * @depends testConstructor
     */
    public function testAddStream()
    {
        $locator = new ResourceLocator();
        $this->assertFalse($locator->schemeExists('bar'));

        $stream = new ResourceStream('bar', '', 'foo');
        $locator->addStream($stream);

        $this->assertTrue($locator->schemeExists('bar'));

        $barStream = $locator->getStream('bar');
        $this->assertIsArray($barStream);
        $this->assertInstanceOf(ResourceStreamInterface::class, $barStream[''][0]);
        $this->assertEquals('foo', $barStream[''][0]->getPath());
    }

    /**
     * @depends testConstructor
     */
    public function testRegisterStream()
    {
        $locator = new ResourceLocator();
        $this->assertFalse($locator->schemeExists('bar'));

        $locator->registerStream('bar', '', 'foo');

        $this->assertTrue($locator->schemeExists('bar'));

        $barStream = $locator->getStream('bar');
        $this->assertIsArray($barStream);
        $this->assertInstanceOf(ResourceStreamInterface::class, $barStream[''][0]);
        $this->assertEquals('foo', $barStream[''][0]->getPath());
    }

    /**
     * @depends testRegisterStream
     */
    public function testRegisterSharedStream()
    {
        $locator = new ResourceLocator();
        $this->assertFalse($locator->schemeExists('bar'));

        $locator->registerStream('bar', '', 'foo', true);

        $this->assertTrue($locator->schemeExists('bar'));

        $barStream = $locator->getStream('bar');
        $this->assertIsArray($barStream);
        $this->assertInstanceOf(ResourceStreamInterface::class, $barStream[''][0]);
        $this->assertEquals('foo', $barStream[''][0]->getPath());
        $this->assertTrue($barStream[''][0]->isShared());
    }

    /**
     * @depends testRegisterSharedStream
     */
    public function testRegisterSharedStreamShort()
    {
        $locator = new ResourceLocator();
        $this->assertFalse($locator->schemeExists('bar'));

        $locator->registerSharedStream('bar', '', 'foo');

        $this->assertTrue($locator->schemeExists('bar'));

        $barStream = $locator->getStream('bar');
        $this->assertIsArray($barStream);
        $this->assertInstanceOf(ResourceStreamInterface::class, $barStream[''][0]);
        $this->assertEquals('foo', $barStream[''][0]->getPath());
        $this->assertTrue($barStream[''][0]->isShared());
    }

    /**
     * @depends testRegisterStream
     */
    public function testRegisterStreamWithPrefix()
    {
        $locator = new ResourceLocator();
        $this->assertFalse($locator->schemeExists('bar'));

        $locator->registerStream('bar', '', 'foo');
        $locator->registerStream('bar', 'prefix', 'oof');

        $this->assertTrue($locator->schemeExists('bar'));

        $barStream = $locator->getStream('bar');
        $this->assertIsArray($barStream);
        $this->assertInstanceOf(ResourceStreamInterface::class, $barStream['prefix'][0]);
        $this->assertEquals('foo', $barStream[''][0]->getPath());
        $this->assertEquals('oof', $barStream['prefix'][0]->getPath());
    }

    /**
     * @depends testRegisterStream
     */
    public function testRegisterStreamWithOutPath()
    {
        $locator = new ResourceLocator();
        $this->assertFalse($locator->schemeExists('bar'));

        $locator->registerStream('bar');

        $this->assertTrue($locator->schemeExists('bar'));

        $barStream = $locator->getStream('bar');
        $this->assertIsArray($barStream);
        $this->assertInstanceOf(ResourceStreamInterface::class, $barStream[''][0]);
        $this->assertEquals('bar', $barStream[''][0]->getPath());
    }

    /**
     * @depends testRegisterStream
     */
    public function testStreamNotFoundException()
    {
        $locator = new ResourceLocator();
        $this->expectException(StreamNotFoundException::class);
        $locator->getStream('etc');
    }

    /**
     * @depends testRegisterStream
     */
    public function testAddStreamThrowExceptionOnRestrictedScheme()
    {
        $locator = new ResourceLocator();
        $this->expectException(\InvalidArgumentException::class);
        $locator->registerStream('file');
    }

    /**
     * @depends testRegisterStream
     */
    public function testRemoveStream()
    {
        $locator = new ResourceLocator();
        $locator->registerStream('bar');
        $this->assertTrue($locator->schemeExists('bar'));
        $locator->removeStream('bar');
        $this->assertFalse($locator->schemeExists('bar'));
    }

    /**
     * @dataProvider addPathProvider
     *
     * @param string       $scheme
     * @param string       $path
     * @param string|array $lookup
     */
    public function testAddPath($scheme, $path, $lookup)
    {
        $locator = new ResourceLocator();

        $this->assertFalse($locator->schemeExists($scheme));

        $locator->addPath($scheme, $path, $lookup);

        $this->assertTrue($locator->schemeExists($scheme));
    }

    /**
     * Data provider for testAddPath.
     */
    public function addPathProvider()
    {
        return [
            ['base', '', 'base'],
            ['local', '', 'local'],
            ['override', '', 'override'],
            ['all', '', ['override://all', 'local://all', 'base://all']],
        ];
    }

    /**
     * @depends testConstructor
     */
    public function testGetStreams()
    {
        $locator = new ResourceLocator();
        $locator->registerStream('bar');
        $locator->registerStream('foo');

        $streams = $locator->getStreams();
        $this->assertIsArray($streams);
        $this->assertCount(2, $streams);
        $this->assertInstanceOf(ResourceStreamInterface::class, $streams['bar'][''][0]);
        $this->assertEquals('bar', $streams['bar'][''][0]->getPath());
    }

    /**
     * @depends testConstructor
     */
    public function testListStreams()
    {
        $locator = new ResourceLocator();
        $locator->registerStream('bar');
        $locator->registerStream('foo');

        $this->assertEquals(['bar', 'foo'], $locator->listStreams());
    }

    /**
     * @depends testConstructor
     */
    public function testIsStream()
    {
        $locator = new ResourceLocator();
        $locator->registerStream('foo');

        $this->assertFalse($locator->isStream('cars://foo.txt'));
        $this->assertTrue($locator->isStream('foo://cars'));
    }

    /**
     * @depends testIsStream
     */
    public function testIsStreamReturnFalseOnBadUri()
    {
        $locator = new ResourceLocator();
        $this->assertFalse($locator->isStream('path/to/../../../file.txt'));
    }

    /**
     * @depends testConstructor
     */
    public function testAddLocation()
    {
        $locator = new ResourceLocator();

        $location = new ResourceLocation('bar', '/foo');
        $locator->addLocation($location);

        $barLocation = $locator->getLocation('bar');
        $this->assertInstanceOf(ResourceLocationInterface::class, $barLocation);
        $this->assertEquals('/foo', $barLocation->getPath());
    }

    /**
     * @depends testAddLocation
     */
    public function testRegisterLocation()
    {
        $locator = new ResourceLocator();

        $locator->registerLocation('bar', '/foo');

        $barLocation = $locator->getLocation('bar');
        $this->assertInstanceOf(ResourceLocationInterface::class, $barLocation);
        $this->assertEquals('/foo', $barLocation->getPath());
    }

    /**
     * @depends testAddLocation
     */
    public function testRegisterLocationWithNoPath()
    {
        $locator = new ResourceLocator();

        $locator->registerLocation('blah');

        $barLocation = $locator->getLocation('blah');
        $this->assertInstanceOf(ResourceLocationInterface::class, $barLocation);
        $this->assertEquals('blah', $barLocation->getPath());
    }

    /**
     * @depends testAddLocation
     */
    public function testGetLocationThrowExceptionIfNotFound()
    {
        $locator = new ResourceLocator();
        $this->expectException(LocationNotFoundException::class);
        $locator->getLocation('etc');
    }

    /**
     * @depends testRegisterLocation
     */
    public function testGetLocations()
    {
        $locator = new ResourceLocator();
        $locator->registerLocation('bar', '/foo');
        $locator->registerLocation('foo', '/bar');

        $locations = $locator->getLocations();
        $this->assertIsArray($locations);
        $this->assertCount(2, $locations);
        $this->assertInstanceOf(ResourceLocationInterface::class, $locations['bar']);
        $this->assertEquals('/foo', $locations['bar']->getPath());
    }

    /**
     * @depends testRegisterLocation
     */
    public function testListLocations()
    {
        $locator = new ResourceLocator();
        $locator->registerLocation('bar', '/foo');
        $locator->registerLocation('foo', '/bar');

        // N.B.: Locations are list with the latest one (top priority) first
        $this->assertEquals(['foo', 'bar'], $locator->listLocations());
    }

    /**
     * @depends testRegisterLocation
     */
    public function testRemoveLocation()
    {
        $locator = new ResourceLocator();
        $locator->registerLocation('bar', '/foo');
        $locator->registerLocation('foo', '/bar');

        $locator->removeLocation('bar');
        $this->assertCount(1, $locator->getLocations());
        $this->assertFalse($locator->locationExist('bar'));
        $this->assertTrue($locator->locationExist('foo'));
    }

    /**
     * @depends testGetLocations
     * @depends testGetStreams
     */
    public function testResourceLocator_reset()
    {
        $locator = new ResourceLocator();
        $locator->registerLocation('bar');
        $locator->registerLocation('foo');
        $locator->registerStream('bar');
        $locator->registerStream('foo');

        $this->assertCount(2, $locator->getStreams());
        $this->assertCount(2, $locator->getLocations());

        $locator->reset();

        $this->assertCount(0, $locator->getStreams());
        $this->assertCount(0, $locator->getLocations());
    }

    /**
     * @param string $uri
     * @param string $path
     * @dataProvider normalizeProvider
     */
    public function testNormalize($uri, $path)
    {
        $locator = new ResourceLocator();
        $this->assertEquals($path, $locator->normalize($uri));
    }

    /**
     * Data provider for testNormalize.
     */
    public function normalizeProvider()
    {
        return [
            ['', ''],
            ['./', ''],
            ['././/./', ''],
            ['././/../', false],
            ['/', '/'],
            ['//', '/'],
            ['///', '/'],
            ['/././', '/'],
            ['foo', 'foo'],
            ['/foo', '/foo'],
            ['//foo', '/foo'],
            ['/foo/', '/foo/'],
            ['//foo//', '/foo/'],
            ['path/to/file.txt', 'path/to/file.txt'],
            ['path/to/../file.txt', 'path/file.txt'],
            ['path/to/../../file.txt', 'file.txt'],
            ['path/to/../../../file.txt', false],
            ['/path/to/file.txt', '/path/to/file.txt'],
            ['/path/to/../file.txt', '/path/file.txt'],
            ['/path/to/../../file.txt', '/file.txt'],
            ['/path/to/../../../file.txt', false],
            ['c:\\', 'c:/'],
            ['c:\\path\\to\file.txt', 'c:/path/to/file.txt'],
            ['c:\\path\\to\../file.txt', 'c:/path/file.txt'],
            ['c:\\path\\to\../../file.txt', 'c:/file.txt'],
            ['c:\\path\\to\../../../file.txt', false],
            ['stream://path/to/file.txt', 'stream://path/to/file.txt'],
            ['stream://path/to/../file.txt', 'stream://path/file.txt'],
            ['stream://path/to/../../file.txt', 'stream://file.txt'],
            ['stream://path/to/../../../file.txt', false],

        ];
    }

    /**
     * @depends testNormalize
     */
    public function testNormalizeReturnFalseOnSuppressedException()
    {
        $locator = new ResourceLocator();
        $this->assertFalse($locator->normalize(123));
    }

    /**
     * @depends testNormalizeReturnFalseOnSuppressedException
     */
    public function testNormalizeThrowExceptionOnBadUri()
    {
        $locator = new ResourceLocator();
        $this->expectException(\BadMethodCallException::class);
        $locator->normalize(123, true);
    }

    /**
     * @depends testNormalizeReturnFalseOnSuppressedException
     */
    public function testNormalizeThrowExceptionOnBadUriPart()
    {
        $locator = new ResourceLocator();
        $this->expectException(\BadMethodCallException::class);
        $locator->normalize('path/to/../../../file.txt', true);
    }

    /**
     * Test issue for stream with empty path adding an extra `/`
     * Test for issue #16.
     */
    public function testStreamWithEmptyPath(): void
    {
        $locator = new ResourceLocator(__DIR__);
        $locator->registerStream('sprinkles', '', '');
        $locator->registerLocation('uploads', 'app/uploads/profile');

        $result = $locator->findResource('sprinkles://'.'header.json', false);

        //NB.: __DIR__ doesn't end with a '/'.
        $this->assertSame('app/uploads/profile/header.json', $result);
        $this->assertNotSame('app/uploads/profile//header.json', $result);
    }

    /**
     * With stream poiting to `app/uploads/profile`, we make sure we can't access `app/uploads/MyFile.txt`.
     */
    public function testFindResourceWithBackPath(): void
    {
        $locator = new ResourceLocator(__DIR__);
        $locator->registerStream('sprinkles', '', '');
        $locator->registerLocation('uploads', 'app/uploads/profile');

        $result = $locator->findResource('sprinkles://'.'../MyFile.txt');

        $this->assertFalse($result);
    }
}
