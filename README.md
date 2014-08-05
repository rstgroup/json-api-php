json-api-php
============
[![Build Status](https://travis-ci.org/rstgroup/json-api-php.svg?branch=master)](https://travis-ci.org/rstgroup/json-api-php) [![Made with passion - RST Group](https://s3-eu-west-1.amazonaws.com/uploads-eu.hipchat.com/84440/610454/wsIVrnw2yOvgyfI/withpassion.png)](https://github.com/rstgroup)


It's a lightweight PHP library for creating json-api standard responses. If you want to know more about the json-api standard, please see the details at http://jsonapi.org.

# About this doc
This document is not finished yet, it's only a draft for now. 

# Usage

## Quick example
```php
<?php

use RstGroup\JsonApiPhp\EntityInterface;
use RstGroup\JsonApiPhp\Resource;
use RstGroup\JsonApiPhp\Relation;
use RstGroup\JsonApiPhp\Writer;

// define posts resource
$postsResource = new Resource();
$postsResource->setCollectionName('posts');
$postsResource->setName('post');
$postsResource->setHref('/posts/{posts.id}');

// create post entities
$post1 = new Post(1, 'first post');
$post2 = new Post(2, 'second awesome post');

$postsResource->setEntities(array($post1, $post2));

// render result
$writer = new Writer();
echo json_encode($writer->write($postsResource));

```
```json
{"posts": [
    {
        "id": "1",
        "href": "/posts/1",
        "text": "first post"
    },
    {
        "id": "2",
        "href": "/posts/2",
        "text": "second awesome post"
    }
]}
```

## Introduction
As an example we take two resources: "authors" and "posts". Author creates posts; he can have many posts, but every single post is related to only one author.

The first thing that we should do is define our resources.

## Defining resources
Every single resource class should extend the (surprise!) `Resource` class. However, you don't need to inherit from the `Resource` class if you don't want to. For example:

```php
<?php

use RstGroup\JsonApiPhp\EntityInterface;
use RstGroup\JsonApiPhp\Resource;
use RstGroup\JsonApiPhp\Relation;
use RstGroup\JsonApiPhp\Writer;

// authors
$authorsResource = new Resource();
$authorsResource->setCollectionName('authors');
$authorsResource->setName('author');
$authorsResource->setHref('/authors/{authors.id}');

// posts
$postsResource = new Resource();
$postsResource->setCollectionName('posts');
$postsResource->setName('post');
$postsResource->setHref('/posts/{posts.id}');
```

As you can see, every resource requires these three properties to be defined:
* collection name
* name
* href

The first two of them are used by the `Writer` class in order to apply naming conventions specified by the standard. "href" is used to prepare full link to a particular resource. `Name` stands for name of a single entity. `Collection name` stands for plural name of resource entities, while `href` should contain url template for a single resource entity.

Ok, now we know what kind of resources we have, what are their names, collection names, and links. You may have noticed that links are set as templates with placeholders, e.g. `{authors.id}`. Properly prepared placeholders are used by `Writer` to determine which parts should be replaced with values (and for a few other things, too). It will be helpful with more complex links, for example: `/authors/{authors.id}/posts/{posts.id}`.

>It's important to know that a placeholder **MUST** have a form like this:
>{_RESOURCE_COLLECTION_NAME_.id}


Now it's time for entities.

## Defining entities
The very first entity we would like to create is `Author`. As you can see below, the `Author` class implements `EntityInterface`. It means that two methods should be implemented: `getId()` and `toArray()`.  
`getId()` returns id of a particular entity and it's used in every place in the library where that id should be known.

`toArray()` should return those properties of an `Author` class that are considered resource fields in the response representation. In the `Author` class these will be `firstName` and `lastName`, as `id` will be added automatically.

```php
class Author implements EntityInterface
{
    protected $id;
    protected $firstName;
    protected $lastName;

    public function __construct($id, $firstName, $lastName)
    {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function getId()
    {
        return $this->id;
    }

    public function toArray()
    {
        return array(
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
        );
    }
}
```

```php
class Post implements EntityInterface
{
    protected $id;
    protected $text;

    public function __construct($id, $text)
    {
        $this->id = $id;
        $this->text = $text;
    }

    public function getId()
    {
        return $this->id;
    }

    public function toArray()
    {
        return array(
            'text' => $this->text,
        );
    }
}
```

Now we're ready to prepare our first json-api standard result:

```php
...
$author1 = new Author(10, 'John', 'Doe');
$author2 = new Author(20, 'Adam', 'Novak');
$authorsResource->setEntities(array($author1, $author2));

$writer = new Writer();
$result = $writer->write($authorsResource);

echo json_encode($result);
```

And the result looks like this:

```json
{"authors": [
    {
        "id": "10",
        "href": "/authors/10",
        "firstName": "John",
        "lastName": "Doe"
    },
    {
        "id": "20",
        "href": "/authors/20",
        "firstName": "Adam",
        "lastName": "Novak"
    }
]}
```
So, we have an authors list that could be achieved via, for example `http://api.example.com/authors` link, but what about related resources like `posts`? Well, for that we need to define resource relations.


## Adding resource relations 
### One-to-one
As it was said earlier, every single post can have a relation to just one author. Let's say that we have two posts, added by the same author. For that we need to modify the `Post` class a little:

```php
class Post implements EntityInterface
{
    ...

    /**
    * @var Author
     */
    protected $author;

    /**
     * @param Author $author
     * @return $this
     */
    public function setAuthor(Author $author)
    {
        $this->author = $author;
        return $this;
    }

    /**
     * @return Author
     */
    public function getAuthor()
    {
        return $this->author;
    }

   ...
}
```
What we've just done here was adding the `$author` property and its setter and getter methods. Author should be an instance of the `Author` entity.

>Hint: we **DID NOT** add our `$author` property to the list of entity fields returned by the `toArray()` method, because `$author` has only one purpose: to hold data of author related to the post.

```php
...
$author1 = new Author(10, 'John', 'Doe');

$post1 = new Post(1, 'first post');
$post1->setAuthor($author1);

$post2 = new Post(2, 'second awesome post');
$post2->setAuthor($author1);

$postsResource->setEntities(array($post1, $post2));
```

Setter is not as important as getter---we could use constructor for that as well---but getter method in this case **MUST be named exactly** `getAuthor()`. Why? Because `Writer` will automatically determine the name of a needed getter method based on:

* relation type (one-to-one/one-to-many)
* resource name/collection name (here: author/authors)

In our case one post is related to one author, so the relation type is `one-to-one`. Therefore, the "author" resource name will be used as the name of the getter method. In case of a `one-to-many` relation (like author having many posts), collection name ("posts") of the `Post` resource will be used. Because of that, `getPosts()` from the `Author` entity class will serve as the getter method.

Anyway, we create relation between post and its author like this:
``` php
...
$postsResource->addRelation(new Relation(Relation::TO_ONE, $authorsResource));

$writer = new Writer();
$result = $writer->write($postsResource);

echo json_encode($result);
```

And the result is:
```json
{"posts": [
    {
        "id": "1",
        "href": "/posts/1",
        "text": "first post",
        "links": {
            "author": {
                "id": "10",
                "href": "/authors/10",
                "type": "authors"
            }
        }
    },
    {
        "id": "2",
        "href": "/posts/2",
        "text": "second awesome post",
        "links": {
            "author": {
                "id": "10",
                "href": "/authors/10",
                "type": "authors"
            }
        }
    }
]}
```

As you can see, new fields called `links` were added to the result in case of every post entity.  
Field `links` contains short info about author related to a particular post. It could be returned in two (three for one-to-many relation type) forms: either as an id or as an object. The latter takes place by default, in order to change this behaviour you need to call:

```php
$writer->setLinkForm(Writer::AS_ID);
```
The result will be:
```json
{"posts": [
    {
        "id": "1",
        "href": "/posts/1",
        "text": "first post",
        "links": {
            "author": "10"
        }
    },
    {
        "id": "2",
        "href": "/posts/2",
        "text": "second awesome post",
        "links": {
            "author": "10"
        }
    }
]
...
```

Or you could just---for some strange reasons---switch that off:
```php
$writer->attachResourceObjectsLinks(false);
```
The result will be:
```json
{"posts": [
    {
        "id": "1",
        "href": "/posts/1",
        "text": "first post",
    },
    {
        "id": "2",
        "href": "/posts/2",
        "text": "second awesome post",
    }
],
...
```

### One-to-many
For now we know how to define a `one-to-one` relation (post has an author), but what about `one-to-many` relation (author has posts)? This is how it should be done in case of the `Author` class:
```php
class Author implements EntityInterface
{
    ...
    /**
     * @var Post[]
     */
    protected $posts;

    /**
     * @param Post[] $posts
     * @return $this
     */
    public function setPosts(array $posts)
    {
        $this->posts = $posts;
        return $this;
    }

    /**
     * @return Post[]
     */
    public function getPosts()
    {
        return $this->posts;
    }
    ...
}
```
... and next:

```php
$post1 = new Post(1, 'first post');
$post2 = new Post(2, 'second awesome post');

$author1 = new Author(10, 'John', 'Doe');
$author1->setPosts(array($post1, $post2));

$authorsResource->setEntities(array($author1));
$authorsResource->addRelation(new Relation(Relation::TO_MANY, $postsResource));

$writer = new Writer();
echo json_encode($writer->write($authorsResource));
```

So the result looks like this:

```json
{"authors": [
    {
        "id": "10",
        "href": "/authors/10",
        "firstName": "John",
        "lastName": "Doe",
        "links": {
            "posts": {
                "ids": ["1", "2"],
                "href": "/posts/1,2",
                "type": "posts"
            }
        }
    }
]}
```

## Embedding related resources data
As mentioned in the previous section, adding relation to a resource causes the `links` field to appear in every entity. However, if you want data on related resource to be embedded in response representation, you should fill entity object up with proper values, and turn on attaching `linked` objects to representation, because it's disabled by default.

In our case filling up is already done as we've set all the properties earlier via constructor:
```php
$author1 = new Author(10, 'John', 'Doe');
...
$post1 = new Post(1, 'first post');
$post2 = new Post(2, 'second awesome post');
```
So there's only one thing left to do:

```php
$writer->setAttachLinked(true);
```

As we can see, the top-level `linked` field contains entities with data on resources related to the author resource:
```json
{"authors": [
    {
        "id": "10",
        "href": "/authors/10",
        "firstName": "John",
        "lastName": "Doe",
        "links": {
            "posts": {
                "ids": ["1", "2"],
                "href": "/posts/1,2",
                "type": "posts"
            }
        }
    }
], "linked": {
    "posts": [
        {
            "id": "1",
            "href": "/posts/1",
            "text": "first post"
        },
        {
            "id": "2",
            "href": "/posts/2",
            "text": "second awesome post"
        }
    ]
}}
```

## Url templates
Url templates can be used to describe URL format for resources according to their type. You can add your url templates like this:

```php
$authorTemplate = new Template('posts.author', '/authors/{posts.author.id}', 'authors');
$postsResource->addTemplate($authorTemplate);
```

```json
{
    "links": {
        "posts.author": "/authors/{posts.author.id}"
    },
    "posts": [
        {
            "id": "1",
            "href": "/posts/1",
            "text": "first post",
            "links": {
                "author": {
                    "id": "111",
                    "href": "/authors/111",
                    "type": "authors"
                }
            }
        },
...
}
```

# TODO's
* support for 'meta'.
