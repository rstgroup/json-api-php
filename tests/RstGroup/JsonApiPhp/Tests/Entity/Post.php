<?php

namespace RstGroup\JsonApiPhp\Tests\Entity;

use RstGroup\JsonApiPhp\EntityInterface;

class Post implements EntityInterface
{
    protected $id;
    protected $author;
    protected $content;
    protected $comments;
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getAuthor()
    {
        return $this->author;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    
    public function setAuthor($author)
    {
        $this->author = $author;
        return $this;
    }

    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @param mixed $comments
     * @return $this
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComments()
    {
        return $this->comments;
    }

    public function toArray()
    {
        return array(
            'id' => $this->getId(),
            'content' => $this->getContent()
        );
    }
}
