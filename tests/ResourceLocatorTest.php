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

        return $locator;
    }

    /**
     * Same test as testResourceLocator_addStream, but with addPath
     *
     * @param ResourceLocator $locator
     * @depends testResourceLocator
     */
    public function testResourceLocator_addPath(ResourceLocator $locator)
    {
        $this->assertFalse($locator->schemeExists('bar'));
        $locator->addPath('bar', '', '/foo');
        $this->assertTrue($locator->schemeExists('bar'));

        $barStream = $locator->getStream('bar');
        $this->assertInternalType('array', $barStream);
        $this->assertInstanceOf(ResourceStream::class, $barStream[''][0]);
        $this->assertEquals('/foo', $barStream[''][0]->getPath());

        $self = $locator->removeStream('bar');
        $this->assertInstanceOf(ResourceLocator::class, $self);
    }

    /**
     * Test stream manipulation with addStream
     *
     * @param ResourceLocator $locator
     * @depends testResourceLocator
     */
    public function testResourceLocator_addStream(ResourceLocator $locator)
    {
        $this->assertFalse($locator->schemeExists('bar'));
        $this->assertFalse($locator->schemeExists('foo'));

        $stream = new ResourceStream('bar', '', '/foo');
        $locator->addStream($stream);
        $locator->registerStream('foo', '', '/bar');

        $this->assertTrue($locator->schemeExists('bar'));
        $this->assertTrue($locator->schemeExists('foo'));

        $barStream = $locator->getStream('bar');
        $this->assertInternalType('array', $barStream);
        $this->assertInstanceOf(ResourceStream::class, $barStream[''][0]);
        $this->assertEquals('/foo', $barStream[''][0]->getPath());

        return $locator;
    }

    /**
     * ...getStreams
     *
     * @param ResourceLocator $locator
     * @depends testResourceLocator_addStream
     */
    public function testResourceLocator_getStreams(ResourceLocator $locator)
    {
        $streams = $locator->getStreams();
        $this->assertInternalType('array', $streams);
        $this->assertCount(2, $streams);
        $this->assertInstanceOf(ResourceStream::class, $streams['bar'][''][0]);
        $this->assertEquals('/foo', $streams['bar'][''][0]->getPath());
    }

    /**
     * ...listStreams
     *
     * @param ResourceLocator $locator
     * @depends testResourceLocator_addStream
     */
    public function testResourceLocator_listStreams(ResourceLocator $locator)
    {
        $this->assertEquals(['bar', 'foo'], $locator->listStreams());
    }

    /**
     * ...removeStream
     *
     * @param ResourceLocator $locator
     * @depends testResourceLocator_addStream
     */
    public function testResourceLocator_removeStream(ResourceLocator $locator)
    {
        $locator->removeStream('bar');
        $this->assertCount(1, $locator->getStreams());
    }

    /**
     * ...isStream
     *
     * @param ResourceLocator $locator
     * @depends testResourceLocator_addStream
     */
    public function testResourceLocator_isStream(ResourceLocator $locator)
    {
        $this->assertFalse($locator->isStream('cars://foo'));
        $this->assertTrue($locator->isStream('foo://cars'));
    }

    /**
     * ...isStream
     *
     * @param ResourceLocator $locator
     * @depends testResourceLocator_addStream
     */
    public function testResourceLocator_isStreamReturnFalseOnBadUri(ResourceLocator $locator)
    {
        $this->assertFalse($locator->isStream('path/to/../../../file.txt'));
    }

    /**
     * Test location manipulation with addLocation
     *
     * @param ResourceLocator $locator
     * @depends testResourceLocator
     */
    public function testResourceLocator_addLocation(ResourceLocator $locator)
    {
        $location = new ResourceLocation('foo', '/bar');
        $locator->addLocation($location);
        $locator->registerLocation('bar', '/foo');
        $locator->registerLocation('blah');

        return $locator;
    }

    /**
     * ...getLocation
     *
     * @param ResourceLocator $locator
     * @depends testResourceLocator_addLocation
     */
    public function testResourceLocator_getLocation(ResourceLocator $locator)
    {
        $barLocation = $locator->getLocation('bar');
        $this->assertInstanceOf(ResourceLocation::class, $barLocation);
        $this->assertEquals('/foo', $barLocation->getPath());
    }

    /**
     * ...getLocations
     *
     * @param ResourceLocator $locator
     * @depends testResourceLocator_addLocation
     */
    public function testResourceLocator_getLocations(ResourceLocator $locator)
    {
        $locations = $locator->getLocations();
        $this->assertInternalType('array', $locations);
        $this->assertCount(3, $locations);
        $this->assertInstanceOf(ResourceLocation::class, $locations['blah']);
        $this->assertEquals('blah', $locations['blah']->getPath());
    }

    /**
     * ...listLocations
     *
     * @param ResourceLocator $locator
     * @depends testResourceLocator_addLocation
     */
    public function testResourceLocator_listLocations(ResourceLocator $locator)
    {
        $this->assertEquals(['blah', 'bar', 'foo'], $locator->listLocations());
    }

    /**
     * ...removeLocation
     *
     * @param ResourceLocator $locator
     * @depends testResourceLocator_addLocation
     */
    public function testResourceLocator_removeLocation(ResourceLocator $locator)
    {
        $locator->removeLocation('bar');
        $this->assertCount(2, $locator->getLocations());
    }

    /**
     * ...locationExist
     *
     * @param ResourceLocator $locator
     * @depends testResourceLocator_addLocation
     */
    public function testResourceLocator_locationExist(ResourceLocator $locator)
    {
        $this->assertTrue($locator->locationExist('foo'));
        $this->assertFalse($locator->locationExist('bar'));
        $this->assertFalse($locator->locationExist('etc'));
    }

    /**
     * Test reset
     *
     * @param ResourceLocator $locator
     * @depends testResourceLocator_addLocation
     */
    public function testResourceLocator_reset(ResourceLocator $locator)
    {
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
}
