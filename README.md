# Uniform Resource Locator

[![Build Status](https://travis-ci.org/userfrosting/UniformResourceLocator.svg?branch=master)](https://travis-ci.org/userfrosting/UniformResourceLocator)
[![Join the chat at https://chat.userfrosting.com/channel/support](https://demo.rocket.chat/images/join-chat.svg)](https://chat.userfrosting.com/channel/support)

Louis Charette & Alexander Weissman, 2018

The _Uniform Resource Locator_ module handles resource aggregation and stream wrapper related tasks for [UserFrosting](https://github.com/userfrosting/UserFrosting).

# Problem to solve

It's easy to find files when they are located in a single place. It's another task when looking for files scattered across multiple directory. Step into the world of package and dependencies and the nightmare begins.

![](images/Graph.png)

It's like trying to find someone in a one story house vs. a 25 stories office building when you don't know on which floor the person is. This package goal is to help you locate things in that office building without having to search floor by floor each time. In other words, it is a way of aggregating many search paths together.

# Structure and logic

## Some Definitions

Let's start by defining terms used in this context.

### Locator

The locator is the tool used to find resources. It know how many floor there is, what can be found on those floor and will actually search the floor for the resource. It's like the receptionist of the office building that tell you where to find the person you're looking for for.

### Location

Locations are possible places resources could be. Typically, each framework or package in our project will be added to the location list. Locations are the floors of our office building. It's assumed here each package (each floor) has the same structure.

### Stream

A stream is the definition of what we can find. A stream is composed of a **scheme** and a **path**. The **scheme** defines what we are looking for. Are we looking for a person, a conference room, a picture, a template, etc. The **path** is the location of this element inside the location. Where, on each floor, we find people (at desks), picture (in the album) or the templates (in `/style/template`).

The streams themselves creates a Uniform Resource Identifiers or **URI** in the form of `schema://path`. URIs are a very strong concept that decouples resource location completely from the mechanisms of individual frameworks or in this case, locations. Furthermore, context-specific scheme can be used to simply a search path. For example, instead of `file://Bundle/WebProfilerBundle/Resources/config/routing/wdt.json`, a `config` scheme can be used to regroup everything related to the `Bundle/WebProfilerBundle/Resources/config` path: `config://routing/wdt.json`. To relate to our office building metaphor, the URI is the question you ask the receptionist when you're looking for someone.

### Resource

A resource is what you are looking for. A resource could be a template file, a configuration file, an image or any other kind of tangible asset in your project.

## Overlaps and a question of priority

In this concept, multiple locations can contain the same resource. When looking for a specific resource without any knowledge of the location it's in, we can't be presented with both. One most win over the other. This is why locations include the concept of **priority loading**. Simply put, the last location added wins.

It's just like searching the office building, top floor to bottom floor and stopping once you found that guy Greg you were looking for. There might be a Greg a floor below, but we don't care. Top floor Greg wins. This might seams cruel, but when using multiple external packages, you might need to overwrite _something_ one defines with more restrictive of customized data.

## Stream Wrappers

A consequence of using URIs for identifying resources is that they are wrapped a [stream wrapper](http://www.php.net/manual/en/class.streamwrapper.php) around the resource locator. While the locator can return the full path of a resource or other informations using the [resource model](#resource-model), the stream wrapper make it so a resource URI can be used directly with built-in PHP functions such as `fopen` and `file_get_contents`. For example :

```
echo file_get_contents('config://routing/wdt.json');
```

## Shared stream

A shared stream lives outside of our packages structure, where we can find shared resources. To use our office building analogy, it's like the parking garage. Cars can't be found on floors, they belong to the garage. They can also be associated to or used by any floor. So when searching for cars, we won't even looks at the different floors. In other words, a shared stream is not influenced by the locations.

In a software environment, this can be seen as a directory used to write log files for example. A log is not tied to a specific framework or location. They can all write info to it.

## Resource model

When getting info about a particular resource, the locator will typically return instance of the **Resource** model. This model is essentially a representation of a file/location and it's metadata. Those metadata can be used to get the path of the resource. It can also be used to get more detailed informations including in which location the file was found.

# Usage

## Creating the Locator

## Adding Streams

### Registering a stream

```
$stream = new ResourceStream();
$stream->setScheme(...);
$stream->setPath(...);
$stream->setShared(...);
$locator->addStream($stream); 
```

### Creating a new stream

```
$locator->registerStream($scheme, $path, $shared); 
```

## Adding Locations

### Registering a location

```
$location = new ResourceLocation();
$location->setName(...);
$location->setPath(...);
$locator->addLocation($location); 
```

### Creating a new location

```
$locator->registerLocation($name, $path); 
```

## Finding resources

### Getting one resource

### Listing all resources

## Managing Locations

### Listing locations


# Testing

See [the Running Tests](RUNNING_TESTS.md) page.

# References

- [The Power of Uniform Resource Location in PHP](https://web.archive.org/web/20131116092917/http://webmozarts.com/2013/06/19/the-power-of-uniform-resource-location-in-php/)
- [When we should we use stream wrapper and socket in PHP?](https://stackoverflow.com/questions/11222498/when-we-should-we-use-stream-wrapper-and-socket-in-php)
- [rockettheme/toolbox](https://github.com/rockettheme/toolbox)