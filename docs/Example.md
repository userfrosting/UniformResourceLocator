# Real Life Example

## Table of Content

<!-- TOC -->

- [Real Life Example](#real-life-example)
    - [Table of Content](#table-of-content)
    - [Project structure](#project-structure)
        - [Locations](#locations)
        - [Streams](#streams)
        - [File Structure](#file-structure)
            - [Config Stream](#config-stream)
            - [Upload Stream](#upload-stream)
    - [Creating the locator, locations and Streams](#creating-the-locator-locations-and-streams)
    - [Finding Files](#finding-files)
    - [Finding Directories](#finding-directories)
    - [Listing Files](#listing-files)
    - [Managing Streams](#managing-streams)
    - [Managing Locations](#managing-locations)
    - [Final Code](#final-code)

<!-- /TOC -->

## Project structure

For this example, we'll use the Building analogy as the fictional structure of our project. We'll use two **floors**, `Floor1` and `Floor2`, as locations, with the second floor (`Floor2`) having priority over the first one (`Floor1`). Each floor will be physically located into `app/floors/FloorX`. Each location will support a stream (`config`). We'll finally add an `upload` shared stream. This stream will be located directly into `/app/uploads/`.

The next tables summarized what we'll be creating :

### Locations

Name   | Path           | Resolved Path
-------|----------------|-------------------
Floor1 | floors/Floor1/ | app/floors/Floor1/
Floor2 | floors/Floor2/ | app/floors/Floor2/

### Streams

Scheme       | Path              | Resolved Path                         | Shared
-------------|-------------------|---------------------------------------|-------
config://    | config/           | app/floors/Floor{X}/config/           | No
upload://    | uploads/          | app/uploads/                          | Yes

### File Structure

#### Config Stream

Location | File            | URI                      | Full Path
---------|-----------------|--------------------------|-----------------------------------------
Floor1   | default.json    | config://default.json    | app/floors/Floor1/config/default.json
Floor1   | debug.json      | config://debug.json      | app/floors/Floor1/config/debug.json
Floor2   | default.json    | config://default.json    | app/floors/Floor2/config/default.json
Floor2   | production.json | config://production.json | app/floors/Floor2/config/production.json
Floor2   | foo/bar.json    | config://foo/bar.json    | app/floors/Floor2/config/foo/bar.json

As you can see from the table above, two locations returns a file for the `config://default.json` URI. Since we established Floor2 has priority over Floor1, the Floor1 file will be overwritten by the Floor2 version when using this URI.

#### Upload Stream

File                 | URI                           | Path
---------------------|-------------------------------|---------------------------------
MyFile.txt           | upload://MyFile.txt           | app/uploads/MyFile.txt
profile/picture.json | upload://profile/picture.json | app/uploads/profile/picture.json
profile/header.json  | upload://profile/header.json  | app/uploads/profile/header.json

## Creating the locator, locations and Streams

First, let's create the locator instance. We'll pass the base path, `app/`, as the constructor only argument. Here you should be careful with the relative location of your code from the rest of your code. The `__DIR__` constant might be helpful in most cases and assuming your code is at the top level of your project (`/`), `__DIR__ . 'app/'` should do it. Note the locator support both relative an absolute base path.

```
$locator = new ResourceLocator(__DIR__ . '/app/');
```

Next, we'll create the locations. The order in which the locations are registered is important. Since we want the second location to have priority, it must be created last. The last registered location always has the highest priority.

```
$locator->registerLocation('Floor1', 'floors/Floor1/');
$locator->registerLocation('Floor2', 'floors/Floor2/');
```

Finally, we'll create the streams. Note the order is not important here. Moreover, the streams can be registered before the locations.

```
$locator->registerStream('config');
$locator->registerStream('upload', '', 'uploads/', true);
```

Note that for the `config` stream, we don't need to specified the path, as the stream scheme will be automatically used as a path. We'll specified it for `upload`, as the path is different (added `s` at the end).

## Finding Files

First thing we'll want to to is try to find the `default.json` file. As stated earlier, the version from *Floor2* will be returned, as this floor as priority over the first floor/location.

The `findResource` method, which will return the absolute path to the file :

```
echo $locator->findResource('config://default.json');

// '/app/floors/Floor2/config/default.json'
```

If  we want to know more about this file, we can instead use the `getResource` method, which will return an instance of the Resource Class. We can then use the available methods to get more infos on the resource we just found :

```
$defaultResource = $locator->getResource('config://default.json');

echo $defaultResource->getAbsolutePath();
// '/app/floors/Floor2/config/default.json'

echo $defaultResource->getPath();
// 'floors/Floor2/config/default.json'

echo $defaultResource->getBasePath();
// 'default.json'

echo $defaultResource->getBasename();
// 'default.json'

echo $defaultResource->getExtension();
// 'json'

echo $defaultResource->getFilename();
// 'default'

echo $defaultResource->getUri();
// 'config://default.json'
```

If we want to know more about the Location where the file has been found, the `getLocation` method can be used to get the Location instance and this instance can then be used to query more info about the location.

```
$defaultResourceLocation = $defaultResource->getLocation();

echo $defaultResourceLocation->getName();
// 'Floor2'

echo $defaultResourceLocation->getPath();
// 'floors/Floor2'
```

Similarly, the `getStream` method can be used to get info about the stream, which can then be used to get info from the stream.

```
$defaultResourceStream = $defaultResource->getStream();

echo $defaultResourceStream->getPath();
// 'config'

echo $defaultResourceStream->getPrefix();
// ''

echo $defaultResourceStream->getScheme();
// 'config'

echo $defaultResourceStream->isShared();
// false
```

Finally, `findResources` and `getResources` can be used to find all instance of `default.json` in a case where the top priority file doesn't matter, but finding all instances are. The priority will still be reflected in the order the files are returned. Both will return an array of resources, with one returning an array of paths and the other an array of Resource instances.

```
$locator->findResources('config://default.json');

/*
[
    '/app/floors/Floor2/config/default.json',
    '/app/floors/Floor1/config/default.json',
]
*/
```

## Finding Directories

The same methods can be used on directories. This can be used, for example, to find a saving path :

```
$uploadResource = $locator->getResource('upload://profile');

echo $uploadResource->getAbsolutePath();
// '/app/uploads/profile'

echo $uploadResource->getPath();
// 'uploads/profile'

echo $uploadResource->getBasePath();
// 'profile'


$locator->findResources('upload://profile');

/*
[
    '/app/uploads/profile'
]
*/
```


## Listing Files

Find and get resource methods will work with a single resource (file) or path. The `listResources` method can be use to list all resources for a particular URI. This method is equivalent to the `glob` built-in PHP function, but will work across all registered locations.

For example, to get a list of all files at the `config://` URI :

```
$locator->listResources('config://');

/*
[
    '/app/floors/Floor1/config/debug.json',
    '/app/floors/Floor2/config/default.json',
    '/app/floors/Floor2/config/foo/bar.json',
    '/app/floors/Floor2/config/production.json'
]
*/
```

As shown in the above example, the `listResources` method will return resources recursively (shown above with the `foo/bar.json` file being listed). If two files shared the same URI, the file with the highest priority will be returned. To return all files, the `all` flag can be used :

```
$locator->listResources('config://', true);

/*
[
    '/app/floors/Floor1/config/debug.json',
    '/app/floors/Floor1/config/default.json',
    '/app/floors/Floor2/config/default.json',
    '/app/floors/Floor2/config/foo/bar.json',
    '/app/floors/Floor2/config/production.json'
]
*/
```

By default, the list will be sorted alphabetically. The location priority can be preserved with the optional third `$sort` argument. When set to false, the list will be sorted by location then by alphabetical order. This can be useful if a higher priority location can override data from a lower priority one, while not necessarily enforcing file name overrides.

```
$locator->listResources('config://', false, false);

/*
[
    '/app/floors/Floor2/config/default.json',
    '/app/floors/Floor2/config/foo/bar.json',
    '/app/floors/Floor2/config/production.json',
    '/app/floors/Floor1/config/debug.json'
]
*/
```

Of course, the URI can reflect a desired sub directory :

```
$locator->listResources('config://foo/', true);

/*
[
    '/app/floors/Floor2/config/foo/bar.json',
]
*/
```

Also note the `listResources` method will return an array of Resource instances, similar to `getResources`, which will cast to an array of absolute paths.


## Managing Streams

Using the `getStreams` locator method will return a list of the registered streams, in our case `config` and `upload`. An associative array of `scheme => ResourceStream`, will be returned in the order they where registered :

```
$locator->getStreams();

/*
[
    'config' => ...
    'upload' => ...
]
*/
```

To obtain an array of stream scheme, without the class instance, `listStreams` can be used :

```
$locator->listStreams();

/*
[
    'config',
    'upload'
]
*/
```

Finally, to obtain the upload stream, the `getStream` can be used. We can then use the stream own method to get or set details :

```
$locator->getStream('upload');
```

## Managing Locations

Using the `getLocations` locator method will return a list of the registered locations, in our case `Floor1` and `Floor2`. An associative array of `scheme => LocationResource`, will be returned in the **reverse** order they where registered, with the location with the highest priority first. In our case, **Floor2** will be returned before **Floor1** :

```
$locator->getLocations();

/*
[
    'Floor2' => ...
    'Floor1' => ...
]
*/
```

To obtain an array of location names, without the class instance, `listLocations` can be used :

```
$locator->listLocations();

/*
[
    'Floor2',
    'Floor1'
]
*/
```

Finally, to obtain the **Floor1**, the `getLocation` can be used. We can then use the location own method to get or set details :

```
$locator->getLocation('Floor1');
```

## Final Code

The final code for the previous examples is part of the Unit Tests and can be viewed here : [tests/DocTest.php](../tests/DocTest.php).
