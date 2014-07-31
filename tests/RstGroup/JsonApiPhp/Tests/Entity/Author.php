<?php
namespace RstGroup\JsonApiPhp\Tests\Entity;

use RstGroup\JsonApiPhp\EntityInterface;

class Author implements EntityInterface
{
    protected $id;
    protected $name;
    
    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

        public function toArray()
    {
        return array(
            'id' => $this->getId(),
            'name' => $this->getName()
        );
    }
}
