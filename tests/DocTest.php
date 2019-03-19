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
use UserFrosting\UniformResourceLocator\ResourceInterface;
use UserFrosting\UniformResourceLocator\ResourceLocationInterface;
use UserFrosting\UniformResourceLocator\ResourceLocator;
use UserFrosting\UniformResourceLocator\ResourceStreamInterface;

/**
 * Tests for the example code in the docs/Readme.md.
 */
class DocTest extends TestCase
{
    /**
     * Setup the shared locator.
     */
    public function testDocExample()
    {
        // Create Locator
        $locator = new ResourceLocator(__DIR__.'/app/');

        // Register Locations
        $locator->registerLocation('Floor1', 'floors/Floor1/');
        $locator->registerLocation('Floor2', 'floors/Floor2/');

        // Register Streams
        $locator->registerStream('config');
        $locator->registerStream('upload', '', 'uploads/', true);

        // Finding Files
        // 1) Find Resource
        $default = $locator->findResource('config://default.json');
        $this->assertSame($this->getBasePath().'/app/floors/Floor2/config/default.json', $default);

        // 2) getRerouce
        $defaultResource = $locator->getResource('config://default.json');
        $this->assertInstanceOf(ResourceInterface::class, $defaultResource);

        $this->assertSame($this->getBasePath().'/app/floors/Floor2/config/default.json', $defaultResource->getAbsolutePath());
        $this->assertSame('floors/Floor2/config/default.json', $defaultResource->getPath());
        $this->assertSame('default.json', $defaultResource->getBasePath());
        $this->assertSame('default.json', $defaultResource->getBasename());
        $this->assertSame('json', $defaultResource->getExtension());
        $this->assertSame('default', $defaultResource->getFilename());
        $this->assertSame('config://default.json', $defaultResource->getUri());

        // 3) GetLocation
        $defaultResourceLocation = $defaultResource->getLocation();
        $this->assertInstanceOf(ResourceLocationInterface::class, $defaultResourceLocation);

        $this->assertSame('Floor2', $defaultResourceLocation->getName());
        $this->assertSame('floors/Floor2', $defaultResourceLocation->getPath());

        // 4) GetStream
        $defaultResourceStream = $defaultResource->getStream();
        $this->assertInstanceOf(ResourceStreamInterface::class, $defaultResourceStream);

        $this->assertSame('config', $defaultResourceStream->getPath());
        $this->assertSame('', $defaultResourceStream->getPrefix());
        $this->assertSame('config', $defaultResourceStream->getScheme());
        $this->assertSame(false, $defaultResourceStream->isShared());

        // 5) FindResources
        $defaults = $locator->findResources('config://default.json');
        $this->assertSame([
            $this->getBasePath().'/app/floors/Floor2/config/default.json',
            $this->getBasePath().'/app/floors/Floor1/config/default.json',
        ], $defaults);

        // Finding Files - upload://profile
        // 1) Find Resource
        $upload = $locator->findResource('upload://profile');
        $this->assertSame($this->getBasePath().'/app/uploads/profile', $upload);

        // 2) getRerouce
        $uploadResource = $locator->getResource('upload://profile');
        $this->assertInstanceOf(ResourceInterface::class, $uploadResource);

        $this->assertSame($this->getBasePath().'/app/uploads/profile', $uploadResource->getAbsolutePath());
        $this->assertSame('uploads/profile', $uploadResource->getPath());
        $this->assertSame('profile', $uploadResource->getBasePath());
        $this->assertSame('profile', $uploadResource->getBasename());
        $this->assertSame('', $uploadResource->getExtension());
        $this->assertSame('profile', $uploadResource->getFilename());
        $this->assertSame('upload://profile', $uploadResource->getUri());

        // 3) FindResources
        $defaults = $locator->findResources('upload://profile');
        $this->assertSame([
            $this->getBasePath().'/app/uploads/profile',
        ], $defaults);

        // ListResources
        $list = $locator->listResources('config://');
        $this->assertEquals([
            $this->getBasePath().'/app/floors/Floor1/config/debug.json',
            $this->getBasePath().'/app/floors/Floor2/config/default.json',
            $this->getBasePath().'/app/floors/Floor2/config/foo/bar.json',
            $this->getBasePath().'/app/floors/Floor2/config/production.json',
        ], $list);

        // ListResources - All
        $list = $locator->listResources('config://', true);
        $this->assertEquals([
            $this->getBasePath().'/app/floors/Floor1/config/debug.json',
            $this->getBasePath().'/app/floors/Floor1/config/default.json',
            $this->getBasePath().'/app/floors/Floor2/config/default.json',
            $this->getBasePath().'/app/floors/Floor2/config/foo/bar.json',
            $this->getBasePath().'/app/floors/Floor2/config/production.json',
        ], $list);

        // ListResources - Sort
        $list = $locator->listResources('config://', false, false);
        $this->assertEquals([
            $this->getBasePath().'/app/floors/Floor2/config/default.json',
            $this->getBasePath().'/app/floors/Floor2/config/foo/bar.json',
            $this->getBasePath().'/app/floors/Floor2/config/production.json',
            $this->getBasePath().'/app/floors/Floor1/config/debug.json',
        ], $list);

        // ListReources - Folder
        $list = $locator->listResources('config://foo/', true);
        $this->assertEquals([
            $this->getBasePath().'/app/floors/Floor2/config/foo/bar.json',
        ], $list);

        // listStreams
        $streams = $locator->listStreams();
        $this->assertSame([
            'config',
            'upload',
        ], $streams);

        // listLocations
        $locations = $locator->listLocations();
        $this->assertSame([
            'Floor2',
            'Floor1',
        ], $locations);
    }

    protected function getBasePath()
    {
        $locator = new ResourceLocator();

        return $locator->normalize(__DIR__);
    }
}
