<?php
namespace RstGroup\JsonApiPhp\Tests\Entity;

use RstGroup\JsonApiPhp\EntityInterface;

class Comment implements EntityInterface
{
    protected $id;
    protected $content;
    protected $postId;
    
    public function getId()
    {
        return $this->id;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getPostId()
    {
        return $this->postId;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    public function setPostId($postId)
    {
        $this->postId = $postId;
        return $this;
    }

    public function toArray()
    {
        return array(
            'id' => $this->getId(),
            'content' => $this->getContent()
        );
    }
}
