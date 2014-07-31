<?php

/**
 * @author tbedkowski
 */

namespace RstGroup\JsonApiPhp\Tests;

use RstGroup\JsonApiPhp\Template;
use RstGroup\JsonApiPhp\Tests\Entity\Author;
use RstGroup\JsonApiPhp\Tests\Entity\Comment;
use RstGroup\JsonApiPhp\Tests\Entity\Post;
use RstGroup\JsonApiPhp\Writer;
use RstGroup\JsonApiPhp\Resource;
use RstGroup\JsonApiPhp\Relation;

class WriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Writer
     */
    protected $writer;

    public function setUp()
    {
        $this->writer = new Writer();
    }

    /**
     *
     * Expected result (in json)
     * {}
     */
    public function testEmptyPostResource()
    {
        $postResource = $this->getPostResource();
        $result = $this->writer->write($postResource);
        $this->assertEmpty($result);
    }

    /**
     *
     * Expected result (in json)
     * {
     *   posts: [{
     *     id: "1",
     *     content: "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *     href: "/posts/1"
     *   }]
     * }
     */
    public function testSinglePostResource()
    {
        $post = $this->getPostEntity(1);
        $postsResource = $this->getPostResource();
        $postsResource->addEntity($post);

        $this->writer->setAttachResourceObjectHref(true);
        $result = $this->writer->write($postsResource);

        $this->assertPostDataExists($result, 1);
        $this->assertPostData($post, $result['posts'][0]);
    }

    /**
     *
     * Expected result (in json)
     * {
     *   posts: [{
     *     id: "1",
     *     content: "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *   }]
     * }
     */
    public function testSinglePostResourceWithoutHref()
    {
        $post = $this->getPostEntity(1);
        $postsResource = $this->getPostResource();
        $postsResource->addEntity($post);

        $this->writer->setAttachResourceObjectHref(false);
        $result = $this->writer->write($postsResource);

        $this->assertPostDataExists($result, 1);
        $this->assertPostData($post, $result['posts'][0], false);

        $post = $result['posts'][0];
        $this->assertArrayNotHasKey('href', $post);
    }

    /**
     * Expected result (in json)
     * {
     *   "posts": [
     *     {
     *       "id": "1",
     *       "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *       "href": "/posts/1"
     *     },
     *     {
     *       "id": "2",
     *       "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *       "href": "/posts/2"
     *     },
     *     {
     *       "id": "3",
     *       "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *       "href": "/posts/3"
     *     }
     *   ]
     * }
     */
    public function testCollectionPostResource()
    {
        $postsResource = $this->getPostResource();

        $post1 = $this->getPostEntity(1);
        $post2 = $this->getPostEntity(2);
        $post3 = $this->getPostEntity(3);

        $postsResource->setEntities(array($post1, $post2, $post3));

        $this->writer->setAttachResourceObjectHref(true);
        $result = $this->writer->write($postsResource);

        $this->assertPostDataExists($result, 3);

        $this->assertPostData($post1, $result['posts'][0]);
        $this->assertPostData($post2, $result['posts'][1]);
        $this->assertPostData($post3, $result['posts'][2]);
    }

    /**
     * Expected result (in json)
     * {
     *   "posts": [{
     *     "id": "1",
     *     "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *     "href": "/posts/1"
     *     "links": {
     *       "author": "100"
     *     }
     *   }]
     * }
     */
    public function testSinglePostResourceWithAuthorLinkAsId()
    {
        $post = $this->getPostEntity(1, 100);
        $postsResource = $this->getPostResource();
        $postsResource->addEntity($post);

        $authorsResource = $this->getAuthorResource();

        $postAuthorRelation = new Relation(Relation::TO_ONE, $authorsResource);
        $postsResource->addRelation($postAuthorRelation);

        $this->writer->setLinkForm(Writer::AS_ID);
        $this->writer->setAttachResourceObjectHref(true);
        $result = $this->writer->write($postsResource);

        $this->assertPostDataExists($result, 1);

        $postData = $result['posts'][0];
        $this->assertPostData($post, $postData);
        $this->assertPostLinksDataExists($postData, 'author');

        $this->assertSame('100', $postData['links']['author']);
    }

    /**
     * Expected result (in json)
     * {
     *   "posts": [{
     *     "id": "1",
     *     "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *     "href": "/posts/1"
     *     "links": {
     *       "author": {
     *         "id": "100",
     *         "href": "/authors/100",
     *         "type": "authors"
     *       }
     *     }
     *   }]
     * }
     */
    public function testSinglePostResourceWithAuthorLinkAsObject()
    {
        $post = $this->getPostEntity(1, 100);
        $postsResource = $this->getPostResource();
        $postsResource->addEntity($post);

        $authorsResource = $this->getAuthorResource();

        $postAuthorRelation = new Relation(Relation::TO_ONE, $authorsResource);
        $postsResource->addRelation($postAuthorRelation);

        $this->writer->setLinkForm(Writer::AS_OBJECT);
        $this->writer->setAttachResourceObjectHref(true);
        $result = $this->writer->write($postsResource);


        $this->assertPostDataExists($result, 1);

        $postData = $result['posts'][0];
        $this->assertPostData($post, $postData);
        $this->assertPostLinksDataExists($postData, 'author');

        $authorData = $postData['links']['author'];
        $this->assertArrayHasKey('id', $authorData);
        $this->assertSame('100', $authorData['id']);

        $this->assertArrayHasKey('href', $authorData);
        $this->assertSame('/authors/100', $authorData['href']);

        $this->assertArrayHasKey('type', $authorData);
        $this->assertSame('authors', $authorData['type']);
    }

    /**
     * Expected result (in json)
     * {
     *   "posts": [{
     *     "id": "1",
     *     "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *     "href": "/posts/1"
     *     "links": {
     *       "comments": ["11", "12", "13"]
     *     }
     *   }]
     * }
     */
    public function testSinglePostResourceWithCommentsLinkAsId()
    {
        $post = $this->getPostEntity(1);
        $post->setComments(array(
            $this->getCommentEntity(11),
            $this->getCommentEntity(12),
            $this->getCommentEntity(13),
        ));

        $postsResource = $this->getPostResource();
        $commentResource = $this->getCommentResource();
        $postsResource->addEntity($post);
        $postsResource->addRelation(new Relation(Relation::TO_MANY, $commentResource));

        $this->writer->setLinkForm(Writer::AS_ID);
        $this->writer->setAttachResourceObjectHref(true);
        $result = $this->writer->write($postsResource);

        $this->assertPostDataExists($result, 1);

        $postData = $result['posts'][0];
        $this->assertPostData($post, $postData);
        $this->assertPostLinksDataExists($postData, 'comments');

        $this->assertInternalType('array', $postData['links']['comments']);
        $this->assertSame(array('11', '12', '13'), $postData['links']['comments']);
    }

    /**
     * Expected result (in json)
     * {
     *   "posts": [{
     *     "id": "1",
     *     "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *     "href": "/posts/1"
     *     "links": {
     *       "comments": {
     *         "ids": ["11", "12", "13"],
     *         "href": "/comments/11,12,13",
     *         "type": "comments"
     *       }
     *     }
     *   }]
     * }
     */
    public function testSinglePostResourceWithCommentsLinkAsObject()
    {
        $post = $this->getPostEntity(1);
        $postsResource = $this->getPostResource($post);

        $commentResource = $this->getCommentResource();
        $comments = array(
            $this->getCommentEntity(11),
            $this->getCommentEntity(12),
            $this->getCommentEntity(13),
        );

        $post->setComments($comments);

        $postCommentsRelation = new Relation(Relation::TO_MANY, $commentResource);
        $postsResource->addEntity($post);
        $postsResource->addRelation($postCommentsRelation);

        $this->writer->setLinkForm(Writer::AS_OBJECT);
        $result = $this->writer->write($postsResource);

        $this->assertPostDataExists($result, 1);

        $postData = $result['posts'][0];
        $this->assertPostData($post, $postData);
        $this->assertPostLinksDataExists($postData, 'comments');

        $commentsLinkData = $postData['links']['comments'];

        $this->assertInternalType('array', $commentsLinkData);

        $this->assertArrayHasKey('ids', $commentsLinkData);
        $this->assertSame(array('11', '12', '13'), $commentsLinkData['ids']);

        $this->assertArrayHasKey('href', $commentsLinkData);
        $this->assertSame('/comments/11,12,13', $commentsLinkData['href']);

        $this->assertArrayHasKey('type', $commentsLinkData);
        $this->assertSame('comments', $commentsLinkData['type']);
    }

    /**
     * Expected result (in json)
     * {
     *   "posts": [{
     *     "id": "1",
     *     "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *     "href": "/posts/1"
     *     "links": {
 *          "author" : {
     *          "id" : "9",
     *          "href" : "/authors/9",
     *          "type" : "authors",
     *      },
     *       "comments": {
     *         "ids": ["11", "12", "13"],
     *         "href": "/comments/11,12,13",
     *         "type": "comments"
     *       }
     *     }
     *   }]
     * }
     */
    public function testSinglePostResourceWithAuthorAndComments()
    {
        $author = $this->getAuthorEntity(9);

        $comments = array(
            $this->getCommentEntity(11),
            $this->getCommentEntity(12),
            $this->getCommentEntity(13),
        );

        $post = $this->getPostEntity(1);
        $post->setAuthor($author);
        $post->setComments($comments);

        $postsResource = $this->getPostResource($post);
        $postsResource->addEntity($post);
        $postsResource->addRelation(new Relation(Relation::TO_ONE, $this->getAuthorResource()));
        $postsResource->addRelation(new Relation(Relation::TO_MANY, $this->getCommentResource()));

        $this->writer->setLinkForm(Writer::AS_OBJECT);
        $result = $this->writer->write($postsResource);

        $this->assertPostDataExists($result, 1);

        $postData = $result['posts'][0];
        $this->assertPostData($post, $postData);
        $this->assertPostLinksDataExists($postData, 'author');
        $this->assertPostLinksDataExists($postData, 'comments');

        $authorLinkData = $postData['links']['author'];
        $this->assertInternalType('array', $authorLinkData);
        $this->assertArrayHasKey('id', $authorLinkData);
        $this->assertSame('9', $authorLinkData['id']);

        $this->assertArrayHasKey('href', $authorLinkData);
        $this->assertSame('/authors/9', $authorLinkData['href']);


        $commentsLinkData = $postData['links']['comments'];

        $this->assertInternalType('array', $commentsLinkData);

        $this->assertArrayHasKey('ids', $commentsLinkData);
        $this->assertSame(array('11', '12', '13'), $commentsLinkData['ids']);

        $this->assertArrayHasKey('href', $commentsLinkData);
        $this->assertSame('/comments/11,12,13', $commentsLinkData['href']);

        $this->assertArrayHasKey('type', $commentsLinkData);
        $this->assertSame('comments', $commentsLinkData['type']);
    }

    /**
     * Expected result (in json)
     * {
     *   "posts": [{
     *     "id": "1",
     *     "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *     "href": "/posts/1"
     *     "links": {
     *       "comments": [
     *          {
     *           "id": 11,
     *           "href": "/comments/11",
     *           "type": "comments"
     *          },
     *          {
     *           "id": 12,
     *           "href": "/comments/12",
     *           "type": "comments"
     *          },
     *          {
     *           "id": 13,
     *           "href": "/comments/13",
     *           "type": "comments"
     *          }
     *      ]
     *     }
     *   }]
     * }
     */
    public function testPostResourceWithCommentsAsObjectsArray()
    {
        $comments = array(
            $this->getCommentEntity(11),
            $this->getCommentEntity(12),
            $this->getCommentEntity(13),
        );

        $post = $this->getPostEntity(1);
        $post->setComments($comments);

        $postsResource = $this->getPostResource($post);
        $postsResource->addEntity($post);
        $postsResource->addRelation(new Relation(Relation::TO_MANY, $this->getCommentResource()));

        $this->writer->setLinkForm(Writer::AS_OBJECTS_ARRAY);
        $result = $this->writer->write($postsResource);

        $this->assertPostDataExists($result, 1);

        $postData = $result['posts'][0];
        $this->assertPostData($post, $postData);
        $this->assertPostLinksDataExists($postData, 'comments');

        $commentsLinkData = $postData['links']['comments'];

        $this->assertInternalType('array', $commentsLinkData);

        $this->assertEquals(3, count($commentsLinkData));

        foreach ($commentsLinkData as $commentData) {
            $this->assertArrayHasKey('id', $commentData);
            $this->assertArrayHasKey('href', $commentData);
            $this->assertArrayHasKey('type', $commentData);
        }

        $this->assertEquals(11, $commentsLinkData[0]['id']);
        $this->assertEquals(12, $commentsLinkData[1]['id']);
        $this->assertEquals(13, $commentsLinkData[2]['id']);

        $this->assertEquals('/comments/11', $commentsLinkData[0]['href']);
        $this->assertEquals('/comments/12', $commentsLinkData[1]['href']);
        $this->assertEquals('/comments/13', $commentsLinkData[2]['href']);
    }

    /**
     * Expected result (in json)
     * {
     *   "posts": [{
     *     "id": "1",
     *     "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *     "href": "/posts/1"
     *     "links": {
     *       "author": "100"
     *     }
     *   }],
     *   "linked": {
     *     "authors": [{
     *       "id": "100",
     *       "name": "Król Julian",
     *     }]
     *   }
     * }
     */
    public function testSinglePostResourceWithAuthorLinked()
    {
        $post = $this->getPostEntity(1);
        $postsResource = $this->getPostResource();

        $author = $this->getAuthorEntity(100);
        $authorResource = $this->getAuthorResource($author);

        $post->setAuthor($author);

        $postAuthorRelation = new Relation(Relation::TO_ONE, $authorResource, array($author));
        $postsResource->addEntity($post);
        $postsResource->addRelation($postAuthorRelation);

        $this->writer->setLinkForm(Writer::AS_ID);
        $this->writer->setAttachLinked(true);

        $result = $this->writer->write($postsResource);

        $this->assertPostDataExists($result, 1);

        $postData = $result['posts'][0];
        $this->assertPostData($post, $postData);
        $this->assertPostLinksDataExists($postData, 'author');

        $this->assertArrayHasKey('linked', $result);
        $this->assertArrayHasKey('authors', $result['linked']);

        $linkedAuthorData = $result['linked']['authors'][0];
        $this->assertAuthorData($author, $linkedAuthorData, true);
    }

    /**
     * Expected result (in json)
     * {
     *   "posts": [{
     *     "id": "1",
     *     "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *     "href": "/posts/1"
     *     "links": {
     *       "comments": ["11", "12", "13"]
     *     }
     *   }],
     *   "linked": {
     *     "comments": [
     *       {
     *         "id": "11",
     *         "content": :Proin ullamcorper magna est, at adipiscing tortor auctor sed.:,
     *       },
     *       {
     *         "id": "12",
     *         "content": :Proin ullamcorper magna est, at adipiscing tortor auctor sed.:,
     *       },
     *       {
     *         "id": "13",
     *         "content": :Proin ullamcorper magna est, at adipiscing tortor auctor sed.:,
     *       },
     *     ]
     *   }
     * }
     */

    public function testSinglePostResourceWithCommentsLinked()
    {
        $post = $this->getPostEntity(1);
        $postsResource = $this->getPostResource($post);

        $comment1 = $this->getCommentEntity(11);
        $comment2 = $this->getCommentEntity(12);
        $comment3 = $this->getCommentEntity(13);
        $post->setComments(array($comment1, $comment2, $comment3));

        $commentResource = $this->getCommentResource();

        $postCommentsRelation = new Relation(Relation::TO_MANY, $commentResource);
        $postsResource->addEntity($post);
        $postsResource->addRelation($postCommentsRelation);

        $this->writer->setLinkForm(Writer::AS_ID);
        $this->writer->setAttachResourceObjectHref(true);
        $this->writer->setAttachLinked(true);
        $result = $this->writer->write($postsResource);

        $this->assertPostDataExists($result, 1);

        $postData = $result['posts'][0];
        $this->assertPostData($post, $postData);
        $this->assertPostLinksDataExists($postData, 'comments');

        $this->assertInternalType('array', $postData['links']['comments']);
        $this->assertSame(array('11', '12', '13'), $postData['links']['comments']);

        $this->assertArrayHasKey('linked', $result);
        $this->assertArrayHasKey('comments', $result['linked']);

        $this->assertCommentData($comment1, $result['linked']['comments'][0], true);
        $this->assertCommentData($comment2, $result['linked']['comments'][1], true);
        $this->assertCommentData($comment3, $result['linked']['comments'][2], true);
    }

    /**
     * Expected result (in json)
     * {
     *   "posts": [
     *     {
     *       "id": "1",
     *       "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *       "href": "/posts/1"
     *       "links": {
     *         "author": "100"
     *       }
     *     },
     *     {
     *       "id": "2",
     *       "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *       "href": "/posts/2"
     *       "links": {
     *         "author": "101"
     *       }
     *     },
     *     {
     *       "id": "3",
     *       "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *       "href": "/posts/3"
     *       "links": {
     *         "author": "101"
     *       }
     *     }
     *   ],
     *   "linked": {
     *     "authors": [
     *       {
     *         "id": "100",
     *         "name": "Król Julian",
     *       }
     *       {
     *         "id": "101",
     *         "name": "Król Julian",
     *       },
     *     ]
     *   }
     * }
     */
    public function testCollectionPostResourceWithAuthorLinked()
    {
        $post1 = $this->getPostEntity(1);
        $post2 = $this->getPostEntity(2);
        $post3 = $this->getPostEntity(3);

        $author1 = $this->getAuthorEntity(100);
        $author2 = $this->getAuthorEntity(101);

        $post1->setAuthor($author1);
        $post2->setAuthor($author2);
        $post3->setAuthor($author2);

        $postsResource = $this->getPostResource();
        $postsResource->setEntities(array($post1, $post2, $post3));
        $postsResource->addRelation(new Relation(Relation::TO_ONE, $this->getAuthorResource()));

        $this->writer->setLinkForm(Writer::AS_ID);
        $this->writer->setAttachLinked(true);
        $result = $this->writer->write($postsResource);

        $this->assertPostDataExists($result, 3);

        $this->assertArrayHasKey('linked', $result);
        $this->assertArrayHasKey('authors', $result['linked']);

        $this->assertCount(2, $result['linked']['authors']);

        $linkedAuthor1Data = $result['linked']['authors'][0];
        $this->assertAuthorData($author1, $linkedAuthor1Data, true);

        $linkedAuthor2Data = $result['linked']['authors'][1];
        $this->assertAuthorData($author2, $linkedAuthor2Data, true);
    }

    /**
     * Expected result (in json)
     * {
     *   "links": {
     *     "posts.comments": "/posts/{post.id}/comments"
     *   }
     *   "posts": [
     *     {
     *       "id": "1",
     *       "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *       "href": "/posts/1"
     *     },
     *     {
     *       "id": "2",
     *       "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *       "href": "/posts/2"
     *     }
     *   ]
     * }
     */
    public function testCollectionPostResourceWithLink()
    {
        $template = new Template('posts.comments', '/posts/{posts.id}/comments');

        $post1 = $this->getPostEntity(1);
        $post2 = $this->getPostEntity(2);

        $postsResource = $this->getPostResource();
        $postsResource->setEntities(array($post1, $post2));
        $postsResource->addTemplate($template);

        $this->writer->setAttachTemplates(true);
        $result = $this->writer->write($postsResource);

        $this->assertPostDataExists($result, 2);

        $this->assertArrayHasKey('links', $result);
        $this->assertCount(1, $result['links']);

        $this->assertArrayHasKey($template->getKey(), $result['links']);
        $this->assertSame($template->getHref(), $result['links'][$template->getKey()]);
    }

    public function testResourceWithoutHref()
    {
        $post = $this->getPostEntity(1);
        $postsResource = $this->getPostResource();
        $postsResource->addEntity($post);

        $this->writer->setAttachResourceObjectHref(false);
        $result = $this->writer->write($postsResource);

        $this->assertArrayHasKey('posts', $result);
        $this->assertArrayNotHasKey('href', $result['posts'][0]);
    }

    public function testLinksWithMultipleIdsInDocumentLinksHref()
    {
        $post1 = $this->getPostEntity(1);
        $post1->setComments(array(
            $this->getCommentEntity(11),
            $this->getCommentEntity(12),
        ));

        $post2 = $this->getPostEntity(2);
        $post2->setComments(array(
            $this->getCommentEntity(13),
        ));

        $postsResource = $this->getPostResource();
        $postsResource->addEntity($post1);
        $postsResource->addEntity($post2);

        $commentsResource = $this->getCommentResource();
        $commentsResource->setHref('/posts/{posts.id}/comments/{comments.id}');

        $postsResource->addRelation(new Relation(Relation::TO_MANY, $commentsResource));

        $result = $this->writer->write($postsResource);

        $this->assertPostDataExists($result, 2);
        $this->assertArrayHasKey('links', $result['posts'][0]);

        $links = $result['posts'][0]['links'];
        $this->assertArrayHasKey('comments', $links);

        $comments = $links['comments'];
        $this->assertArrayHasKey('href', $comments);
        $this->assertSame('/posts/1/comments/11,12', $comments['href']);

        $links = $result['posts'][1]['links'];
        $this->assertArrayHasKey('comments', $links);

        $comments = $links['comments'];
        $this->assertArrayHasKey('href', $comments);
        $this->assertSame('/posts/2/comments/13', $comments['href']);
    }

    /**
     * Expected result (in json)
     * {
     *   "links": {
     *     "posts.comments": {
     *          "href" : "/posts/1/comments/{posts.comments}",
     *          "type" : "comments"
     *      }
     *   }
     *   "posts": [
     *     {
     *       "id": "1",
     *       "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     *       "href": "/posts/1"
     *     }
     *   ]
     * }
     */
    public function testTemplatesLinksAsObject()
    {
        $postsResource = $this->getPostResource();
        $post = $this->getPostEntity(1);
        $template = new Template('posts.comments', '/posts/1/comments/{posts.comments}', 'comments');

        $postsResource->addEntity($post);
        $postsResource->addTemplate($template);

        $this->writer->setTemplatesLinksAsObject(true);
        $this->writer->setAttachTemplates(true);
        $result = $this->writer->write($postsResource);

        $this->assertArrayHasKey('links', $result);
        $this->assertCount(1, $result['links']);
        $this->assertArrayHasKey($template->getKey(), $result['links']);
        $this->assertTrue(is_array($result['links'][$template->getKey()]));

        $templateLink = $result['links'][$template->getKey()];
        $this->assertArrayHasKey('href', $templateLink);
        $this->assertArrayHasKey('type', $templateLink);

        $this->assertSame($template->getHref(), $templateLink['href']);
        $this->assertSame($template->getType(), $templateLink['type']);
    }


    protected function assertPostDataExists($result, $count)
    {
        $this->assertArrayHasKey('posts', $result);
        $this->assertCount($count, $result['posts']);
    }

    protected function assertPostData(Post $post, $postData, $withHref = true)
    {
        $this->assertArrayHasKey('id', $postData);
        $this->assertEquals($post->getId(), $postData['id']);

        $this->assertArrayHasKey('content', $postData);
        $this->assertSame($post->getContent(), $postData['content']);

        if ($withHref) {
            $this->assertArrayHasKey('href', $postData);
            $this->assertSame("/posts/{$post->getId()}", $postData['href']);
        }
    }

    protected function assertPostLinksDataExists($postData, $name)
    {
        $this->assertArrayHasKey('links', $postData);
        $this->assertArrayHasKey($name, $postData['links']);
    }

    protected function assertAuthorData(Author $author, $authorData)
    {
        $this->assertArrayHasKey('id', $authorData);
        $this->assertEquals($author->getId(), $authorData['id']);

        $this->assertArrayHasKey('name', $authorData);
        $this->assertSame($author->getName(), $authorData['name']);

        $this->assertArrayHasKey('href', $authorData);
        $this->assertSame("/authors/{$author->getId()}", $authorData['href']);
    }

    protected function assertCommentData(Comment $comment, $commentData)
    {
        $this->assertArrayHasKey('id', $commentData);
        $this->assertEquals($comment->getId(), $commentData['id']);

        $this->assertArrayHasKey('content', $commentData);
        $this->assertSame($comment->getContent(), $commentData['content']);

        $this->assertArrayHasKey('href', $commentData);
        $this->assertSame("/comments/{$comment->getId()}", $commentData['href']);
    }

    protected function getPostEntity($id, $authorId = null)
    {
        $post = new Post();
        $post->setId($id)
            ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit.');

        if ($authorId !== null) {
            $author = $this->getAuthorEntity($authorId);
            $post->setAuthor($author);
        }

        return $post;
    }

    protected function getAuthorEntity($authorId)
    {
        $author = new Author();
        $author->setId($authorId)
            ->setName('Król Julian');

        return $author;
    }

    protected function getCommentEntity($commentId, $postId = null)
    {
        $comment = new Comment();
        $comment->setId($commentId)
            ->setContent('Proin ullamcorper magna est, at adipiscing tortor auctor sed.')
            ->setPostId($postId);

        return $comment;
    }

    protected function getPostResource()
    {
        $postResource = new Resource();
        $postResource->setHref('/posts/{posts.id}')
            ->setName('post')
            ->setCollectionName('posts');

        return $postResource;
    }

    protected function getAuthorResource()
    {
        $authorResource = new Resource();
        $authorResource->setHref('/authors/{authors.id}')
            ->setName('author')
            ->setCollectionName('authors');

        return $authorResource;
    }

    protected function getCommentResource()
    {
        $commentResource = new Resource();
        $commentResource->setHref('/comments/{comments.id}')
            ->setName('comment')
            ->setCollectionName('comments');

        return $commentResource;
    }
}