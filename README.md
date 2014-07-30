json-api-php
============

PHP library for creating json-api standard responses. For more details about standard please see details at http://jsonapi.org.

# About this doc
This document is not finished yet, it's only a draft for now. 

# What is json-api-php 
It's a light PHP library for creating json-api standard responses. If You want to know more about json-api standard, please see details at http://jsonapi.org.

# Usage
As example we take two resources: "authors" and "posts".
Author creates posts, so an author can have many posts, but every single post will be related with only one author.<br/>

The first thing that we should do is to define our resources.

## Defining resources
Your every single resource class should extends library `Resource` class ;), but if You want to, You may just use `Resource` class without creating nothing new at all. For example:

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

So every resource should have defined three properties:
* name
* collection name
* href

First two of them are used by `Writer` for naming conventions specified in the standard documentation, last one is used to for preparing full link to concrete resource. `Name` stands for name of single document. `Collection name` stands for plural name of documents, while `href` should contain template url for single document. 

Ok, now we know what kind of resources we have, what are their names, collection names, and links. You may have noticed that links are set as templates with placeholders, e.g. `{authors.id}`. A properly prepared placeholders are used by `Writer` to determine what value should be replaced with it (and for few other things too). It will be helpful with more complex links, for example `/authors/{authors.id}/posts/{posts.id}`. 

>It's important to know that placeholder **MUST** have form like this:
>{_RESOURCE_COLLECTION_NAME_.id}


Now it's time for entities.

## Defining entities
Entities of concrete resource are `documents` within the meaning of json-api standard (see: http://jsonapi.org/format).<br/>
The very first entity we would like to create is `Author`. As You can see below, `Author` class implements `EntityInterface`. It means that it should implement two methods: `getId()` and `toArray()`.<br/>
Method `getId()` returns id of concrete entity and it's used at every place in library where that id should be known.<br/>

Method `toArray()` should return the properties of an `Author` class, that are considered as resource fields in response representation. In our `Author` class it will be `firstName` and `lastName`, as `id` will be added automatically.

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

At this moment we are ready to prepare our first json-api standard result:

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
So, we have authors list that could be achieved via, for example `http://api.example.com/authors` link, but what about related resources like `posts`? Well, for that we need to define resource relations.


## Adding resource relations 
### One-to-one
As it was said earlier, every single post can have relation to one author. Lets say that we have two posts added by the same author. For that we need to change a little our `Post` class:

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
All that we have done here was adding `$author` property and its setter and getter methods. Author should be instance od `Author` entity. 

>Hint: we **DO NOT** add our `$author` property to the list of entity fields returned via `toArray()` method, cause `$author` has only one goal: to hold data of author related with the post.

```php
...
$author1 = new Author(10, 'John', 'Doe');

$post1 = new Post(1, 'first post');
$post1->setAuthor($author1);

$post2 = new Post(2, 'second awesome post');
$post2->setAuthor($author1);

$postsResource->setEntities(array($post1, $post2));
```

Setter is not as important as getter - we could use constructor for that as well - but getter method in this case **MUST be named exactly** `getAuthor()`. Why? Because `Writer` will automatically determine name of needed getter method based on:

* relation type (one-to-one/one-to-many)
* resource name/collection name (here: author/authors)

In our case one post is related with one author, so relation type is `one-to-one`, as such resource name "author" will be used as a name of the getter method. In case of `one-to-many` relation type, like author having many posts, collection name ("posts") of post resource will be used, so name of getter method would be `getPosts()` in `Author` entity class.

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

As You can see there are new fields in result: `links` in every post document.<br/>
Field `links` contains short info about author related with concrete post. It could be returned in two (three for one-to-many relation type) forms: as id only, and as an object (by default). You could change that by:

```php
$writer->setLinkForm(Writer::AS_ID);
```
causing
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

or You could just switch that off (for some strange reasons):
```php
$writer->setAttachDocumentLinks(false);
```
causing
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
For now we know how to define `one-to-one` relation (post has author), but what about `one-to-many` relation type (author has posts)? This is how we do it with `Author` class...
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

So result looks like:

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
As mentioned in previous section, adding relation to resource causes the appearance of new `links` field in every document on list. But, If You want to have embeded data of related resource in response representation You should fill up entity object with proper values, and turn on attaching `linked` objects to representation, cause it is disabled by default.

In our case filling up is already done as we've set all properties earlier via constructor:
```php
$author1 = new Author(10, 'John', 'Doe');
...
$post1 = new Post(1, 'first post');
$post2 = new Post(2, 'second awesome post');
```
So only one thing left to do:

```php
$writer->setAttachLinked(true);
```

And, as we can see, the top-level `linked` field contains documents with data of resources related with author resource:
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

## Url Templates

# Writer

# TODO's
* support for 'meta'.
