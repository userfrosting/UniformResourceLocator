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
class ResourceStreamTest extends TestCase
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
    }

    /**
     * Now try again with the info in the constructor
     */
    public function testResourceStream_ctor()
    {
        $stream = new ResourceStream('bar', '', '/foo', true);
        $this->assertEquals('bar', $stream->getScheme());
        $this->assertEquals('/foo', $stream->getPath());
        $this->assertTrue($stream->isShared());
    }

    /**
     * When no path is defined, the name should be used
     */
    public function testResourceStream_noPath()
    {
        $stream = new ResourceStream('etc');
        $this->assertEquals('etc', $stream->getScheme());
        $this->assertEquals('etc', $stream->getPath());
    }
}