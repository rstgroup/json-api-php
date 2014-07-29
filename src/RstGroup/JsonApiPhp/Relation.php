<?php
/**
 * @author tbedkowski
 */

namespace RstGroup\JsonApiPhp;

class Relation
{
    const TO_ONE  = 'toOne';
    const TO_MANY = 'toMany';

    /**
     * @var string
     */
    protected $relType;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $collectionName;

    /**
     * @var string
     */
    protected $href;

    function __construct($relType, Resource $resource)
    {
        $this->setRelType($relType);
        $this->setName($resource->getName());
        $this->setCollectionName($resource->getCollectionName());
        $this->setHref($resource->getHref());
    }

    /**
     * @param string $relType
     * @return $this
     */
    public function setRelType($relType)
    {
        $this->relType = $relType;
        return $this;
    }

    /**
     * @return string
     */
    public function getRelType()
    {
        return $this->relType;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $collectionName
     * @return $this
     */
    public function setCollectionName($collectionName)
    {
        $this->collectionName = $collectionName;
        return $this;
    }

    /**
     * @return string
     */
    public function getCollectionName()
    {
        return $this->collectionName;
    }

    /**
     * @param string $href
     * @return $this
     */
    public function setHref($href)
    {
        $this->href = $href;
        return $this;
    }

    /**
     * @return string
     */
    public function getHref()
    {
        return $this->href;
    }
}