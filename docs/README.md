# Table of Content

<!-- TOC -->

- [Structure and Logic](#structure-and-logic)
    - [Some Definitions](#some-definitions)
        - [Locator](#locator)
        - [Location](#location)
        - [Stream](#stream)
        - [Resource](#resource)
    - [Overlaps and a Question of Priority](#overlaps-and-a-question-of-priority)
    - [Stream Wrappers](#stream-wrappers)
    - [Shared Stream](#shared-stream)
    - [Resource Model](#resource-model)
    - [Using Scheme Prefix](#using-scheme-prefix)
        - [Prefix and Shared Streams](#prefix-and-shared-streams)
        - [Prefix and Non Shared Streams](#prefix-and-non-shared-streams)
        - [Prefix and Mixed Streams](#prefix-and-mixed-streams)
- [General Usage](#general-usage)
    - [Creating the Locator](#creating-the-locator)
    - [Adding Streams](#adding-streams)
        - [Registering an Existing Stream](#registering-an-existing-stream)
        - [Creating a New Stream](#creating-a-new-stream)
    - [Adding Locations](#adding-locations)
        - [Registering a location](#registering-a-location)
        - [Creating a New location](#creating-a-new-location)
    - [Finding Resources](#finding-resources)
        - [Getting Resources Instance](#getting-resources-instance)
        - [Listing Resources](#listing-resources)
    - [Resource Instance](#resource-instance)
    - [Managing Streams](#managing-streams)
        - [Stream Instance](#stream-instance)
    - [Managing Locations](#managing-locations)
        - [Location Instance](#location-instance)
- [Real Life Example](#real-life-example)

<!-- /TOC -->

# Structure and Logic

## Some Definitions

Let's start by defining terms used in this context.

### Locator

The locator is the tool used to find resources. It know how many floor there is, what can be found on those floor and will actually search the floor for the resource. It's like the receptionist of the office building that tell you where to find the person you're looking for for.

### Location

Locations are possible places resources could be. Typically, each framework or package in our project will be added to the location list. Locations are the floors of our office building. It's assumed here each package (each floor) has the same structure.

### Stream

A stream is the definition of what we can find. A stream is composed of a **scheme** and a **path**. The **scheme** defines what we are looking for. Are we looking for a person, a conference room, a picture, a template, etc? The **path** is the location of this element inside the location. Where, on each floor, we find people (at desks), picture (in the album) or the templates (in `/style/template`).

The streams themselves creates a Uniform Resource Identifiers or **URI** in the form of `schema://path`. URIs are a very strong concept that decouples resource location completely from the mechanisms of individual frameworks or in this case, locations. Furthermore, context-specific scheme can be used to simply a search path. For example, instead of `file://Bundle/WebProfilerBundle/Resources/config/routing/wdt.json`, a `config` scheme can be used to regroup everything related to the `Bundle/WebProfilerBundle/Resources/config` path: `config://routing/wdt.json`. To relate to our office building metaphor, the URI is the question you ask the receptionist when you're looking for someone.

### Resource

A resource is what you are looking for. A resource could be a template file, a configuration file, an image or any other kind of tangible asset in your project.

## Overlaps and a Question of Priority

In this concept, multiple locations can contain the same resource. When looking for a specific resource without any knowledge of the location it's in, we can't be presented with both. One most win over the other. This is why locations include the concept of **priority loading**. Simply put, the last location added wins.

It's just like searching the office building, top floor to bottom floor and stopping once you found that guy Greg you were looking for. There might be a Greg a floor below, but we don't care. Top floor Greg wins. This might seams cruel, but when using multiple external packages, you might need to overwrite _something_ one defines with more restrictive of customized data.

## Stream Wrappers

A consequence of using URIs for identifying resources is that they are wrapped a [stream wrapper](http://www.php.net/manual/en/class.streamwrapper.php) around the resource locator. While the locator can return the full path of a resource or other informations using the [resource model](#resource-model), the stream wrapper make it so a resource URI can be used directly with built-in PHP functions such as `fopen` and `file_get_contents`. For example :

```
echo file_get_contents('config://routing/wdt.json');
```

## Shared Stream

A shared stream lives outside of our packages structure, where we can find shared resources. To use our office building analogy, it's like the parking garage. Cars can't be found on floors, they belong to the garage. They can also be associated to or used by any floor. So when searching for cars, we won't even looks at the different floors. In other words, a shared stream is not influenced by the locations.

In a software environment, this can be seen as a directory used to write log files for example. A log is not tied to a specific framework or location. They can all write info to it.

## Resource Model

When getting info about a particular resource, the locator will typically return instance of the **Resource** model. This model is essentially a representation of a file/location and it's metadata. Those metadata can be used to get the path of the resource. It can also be used to get more detailed informations including in which location the file was found.

## Using Scheme Prefix

### Prefix and Shared Streams

When working with shared streams, prefix can be used to manually define a subpath. Let's look at different stream defined using the `cars` scheme :

| Prefix | Path                  | Uri                    | Search                                 | Real Path                     |
|--------|-----------------------|------------------------|----------------------------------------|-------------------------------|
|        | Building/cars/        | cars://police/blah.txt | police/blah.txt in Building/cars       | Building/cars/police/blah.txt |
| police | Building/cars/police/ | cars://police/blah.txt | blah.txt in Building/cars/police       | Building/cars/police/blah.txt |
| rental | Rental/               | cars://rental/blah.txt | blah.txt in Rental/                    | Rental/blah.txt               |

You can see how a `prefix` can be used so the `cars://rental` URI act as a proxy for the `/Rental` directory. Note on the above table, the first two rows result in the same file being found. Of course this is basically useless, but it shows why you should be careful with prefix and what it's not. Note that a prefix will always overwrite a normal path (one without a prefix).

This also means if there's a file located in `Building/cars/rental/blah.txt` (the first search path), the `cars://rental/blah.txt` URI won't return the `rental/blah.txt` file from the prefix-less search path. Instead, `blah.txt` will be returned from the `rental` prefix search path.

### Prefix and Non Shared Streams

Of course, prefix can also be used with non shared streams. Using the same streams :

| Prefix | Path               |
|--------|--------------------|
|        | files/             |
| data   | upload/data/files/ |

The resulting search paths will take the `Floors` locations into account :

| Uri                    | Search Path                        |
|------------------------|------------------------------------|
| files://test.json      | Floors/{floorX}/files/             |
| files://data/test.json | Floors/{floorX}/upload/data/files/ |

### Prefix and Mixed Streams

Shared and non shared streams can also be mixed when using prefix :

| Prefix | Path               | Shared |
|--------|--------------------|--------|
|        | files/             | no     |
| data   | upload/data/files/ | yes    |

The resulting search paths will then be :

| Uri                    | Search Path            |
|------------------------|------------------------|
| files://test.json      | Floors/{floorX}/files/ |
| files://data/test.json | upload/data/files/     |

In other words...

# General Usage

See [API documentation for more information](api.md#class-userfrostinguniformresourcelocatorresourcelocator).

## Creating the Locator

```
$locator = new ResourceLocator();
```

The locator accept a single optional argument, `$basePath`. This can be used to define the base search path for the locator. In most cases, it will be project root folder.


## Adding Streams

A stream can either be created directly or an existing stream can be registered with the locator.

#### Registering an Existing Stream

```
$stream = new ResourceStream();
$locator->addStream($stream); 
```

#### Creating a New Stream

```
$locator->registerStream($scheme, $prefix, $path, $shared); 
```

## Adding Locations

Similar to streams, a location can either be created or an existing one can be registered with the locator.

### Registering a location

```
$location = new ResourceLocation();
$locator->addLocation($location); 
```

### Creating a New location

```
$locator->registerLocation($name, $path); 
```

## Finding Resources

The `findResource` and `findResources` methods can be used to find paths for the specified URI. While `findResource` will return the top most file, `findResources` will return all the resources available for that URI, sorted by priority.

```
$locator->findResources('config://default.json');

/*
[
    'app/PackageA/config/default.json',
    'app/PackageB/config/default.json',
    'app/PackageC/config/default.json'
]
*/

$locator->findResource('config://default.json');

// 'app/PackageA/config/default.json'
```

by default, absolute paths will be returned. Relative path can be returned by setting the `absolute` flag to false.


### Getting Resources Instance

`getResource` and `getResources` can be used the same way as `findResource` and `findResources`, but instead of returning the path for each assets, a [`Resource`](#Resources) instance will be returned. `getResources` will return an array of all resources, sorted by priority.

### Listing Resources

All available resources in a given directory can be listed using the `listResources` method. This method will also returns the resources recursively, unlike `getResources` or `findResources`.

```
$resources = $locator->listResources('cars://');

/*
[
    'app/PackageA/config/develop.json',
    'app/PackageA/config/testing.json',
    'app/PackageB/config/default.json',
    'app/PackageB/config/test/foo.json',
    'app/PackageC/config/production.json',
]
*/
```

In the above example, if both `PackageA` and `PackageB` have a `default.json` file, the top most version will be returned. To return all instances of every resources, the `all` flag can be used :

```
$resources = $locator->listResources('cars://', true);

/*
[
    'app/PackageA/config/default.json',
    'app/PackageA/config/develop.json',
    'app/PackageA/config/production.json',
    'app/PackageA/config/testing.json',
    'app/PackageB/config/default.json',
    'app/PackageB/config/test/foo.json',
    'app/PackageC/config/production.json',
]
*/
```

## Resource Instance

Resources can be represented using an instance of a class. This can be used to access different metadata about a file in addition to the file path.

Available methods :

- `getAbsolutePath()` : Returns the file absolute path.

- `getPath()` : Returns the file relative path.

- `getBasePath()` : Returns the path that comes after the `://` in the resource URI.

- `getBasename()` : Returns the trailing name component (ex.: foo/test.txt -> test.txt).

- `getExtension()` : Returns the resource extension (foo/test.txt -> txt).

- `getFilename()` : Returns the resource filename (foo/test.txt -> test).

- `getLocation()` : Returns the location instance used to find the resource. Returns Null if it's a shared stream.

- `getStream()` : Returns the stream instance used to find the resource.

- `getUri()` : Returns the URI that can be used to retrieve this resource.

See the [API Documentation](api.md#class-userfrostinguniformresourcelocatorresource) for more informations.


By default, a resource instance will cast as a string containing the absolute path :

```
$resource = $locator->getResource(config://default.json);

echo $resource->getAbsolutePath();
// '/app/PackageB/config/default.json'

echo $resource;
// '/app/PackageB/config/default.json'
```

## Managing Streams

The locator provides some methods to control registered streams. Since stream scheme (the part before the `://`) is unique, most streams are identified using schemes.

- `getStream(string $scheme)` : Returns a stream instance from the stream scheme. Return `StreamNotFoundException` if scheme doesn't match any registered stream.

- `getStreams()` : Returns an array of all the streams registered on the locator. Each stream will be represented by an instance of [Stream Class](#Stream-Instance).

- `isStream(string $uri)` : Returns true if URI is resolvable by using locator. To be resolvable, a URI must be valid and bound to a registered scheme. Any valid URI can be resolvable, either a file or a path.

- `schemeExists(string $scheme)` : Return true or false if a scheme match a registered stream.

- `listStreams()` : Return a list of all the stream scheme registered.

- `removeStream(string $scheme)` : Unregister the stream associated with the specified scheme.

### Stream Instance

When interacting with stream instances, informations can be retrieved or changed using the instance public methods :  

- `getPath` : Return the base path for the stream. In a non shared stream, it would be the relative path inside the location.

- `getPrefix` : Return the [prefix](#Using-Scheme-Prefix) defined for the stream.

- `getScheme` : Return the stream scheme (the part before the `://`).

- `isShared` : Return true or false if a stream is shared.

- `setPath(string $path)` : Change the path for the stream.

- `setPrefix(string $prefix)` : Change the stream prefix.

- `setScheme(string $scheme)` : Change the stream scheme. **A scheme shouldn't be changed once the stream is registered with the locator. This can produce unwanted behavior**.

- `setShared(bool $$shared)` : Change the stream shared status.

See the [API Documentation](api.md#class-userfrostinguniformresourcelocatorresourcestream) for more informations.


## Managing Locations

The locator also provides methods to control registered locations. Each location have a **name** and a **path**.

- `getLocation(string $name)` : Returns a [location instance](#Location-Instance) for the location name. Return `LocationNotFoundException` if the specified name doesn't match any registered location.

- `getLocations()` : Returns an array of all the locations registered on the locator. Each location will be represented by an instance of [location instance](#Location-Instance).

- `listLocations()` : List all available location registered with the locator. Returns an associative array (`name => path`).

- `locationExist(string $name)` : Returns true or false if the specified location name is registered.

- `removeLocation(string $name)` : Unregister the location associated with the specified scheme.

### Location Instance

When interacting with location instances, informations can be retrieved or changed using the instance public methods :  

- `getName` : Return the location name.

- `setName` : Change the location name. **A location name shouldn't be changed once it is registered with the locator. This can produce unwanted behavior**.

- `getPath` : Return the location base path.

- `setPath` : Change the location base path.

See the [API Documentation](api.md#class-userfrostinguniformresourcelocatorresourcestream) for more informations.

# Real Life Example

## Project structure

For this example, we'll use the Building analogy as the fictional structure of our project. We'll use two **floors**, `Floor1` and `Floor2`, as locations, with the second floor (`Floor2`) having priority over the first one (`Floor1`). Each floor will be physically located into `app/floors/FloorX`. Each location will support three streams (`config`, `logs`, `templates`). We'll finally an `upload` shared stream. This stream will be located directly into `/app/uploads/`.

The next tables summarized what we'll be creating :

### Locations

Name   | Path           | Resolved Path
-------|----------------|-------------------
Floor1 | floors/Floor1/ | app/floors/Floor1/
Floor2 | floors/Floor2/ | app/floors/Floor2/

### Streams

Scheme       | Path              | Resolved Path                         | Shared
-------------|-------------------|---------------------------------------|-------
config://    | config/           | app/Floors/Floor{X}/config/           | No
logs://      | logs/             | app/Floors/Floor{X}/logs/             | No
templates:// | layout/templates/ | app/Floors/Floor{X}/layout/templates/ | No
upload://    | uploads/          | app/uploads/                          | Yes

### File Structure

#### Config

! TODO

## Creating the locator, locations and Streams

First, let's create the locator instance. We'll pass the base path, `app/`, as the constructor only argument. Here you should be careful with the relative location of your code from the rest of your code. The `__DIR__` constant might be helpful in most cases and assuming your code is at the top level of your project (`/`), `__DIR__ . 'app/'` should do it. Note the locator support both relative an absolute base path.

```
$locator = new ResourceLocator(__DIR__ . 'app/');
```

Next, we'll create the locations. The order in which the locations are registered is important. Since we want the second location to have priority, it must be created last. The last registered location always has the highest priority.

```
$locator->registerLocation('Floor1', 'floors/Floor1/');
$locator->registerLocation('Floor2', 'floors/Floor2/');
```

Finally, we'll create the streams. Note the order is not important here. Moreover, the streams can be registered before the locations.

```
$locator->registerStream('config');
$locator->registerStream('logs');
$locator->registerStream('templates', '', 'layout/templates/');
$locator->registerStream('upload', '', 'uploads/', true);
```

## Finding Files

