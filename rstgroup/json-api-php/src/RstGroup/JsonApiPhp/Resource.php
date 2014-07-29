<?php
/**
 * @author tbedkowski
 */

namespace RstGroup\JsonApiPhp;

class Resource
{
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

    /**
     * @var Template[]
     */
    protected $templates = array();

    /**
     * @var Relation[]
     */
    protected $relations = array();

    /**
     * @var EntityInterface[]
     */
    protected $entities = array();

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

    /**
     * @param EntityInterface[] $entities
     * @return $this
     */
    public function setEntities( $entities)
    {
        foreach ($entities as $entity) {
            $this->addEntity($entity);
        }
    }

    /**
     * @return EntityInterface[]
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * @param EntityInterface $entity
     */
    public function addEntity(EntityInterface $entity)
    {
        $this->entities[] = $entity;
    }

    /**
     * @param Relation[] $relations
     * @return $this
     */
    public function setRelations($relations)
    {
        foreach ($relations as $relation) {
            $this->addRelation($relation);
        }
        return $this;
    }

    /**
     * @return Relation[]
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * @param Relation $relation
     */
    public function addRelation(Relation $relation)
    {
        $this->relations[] = $relation;
    }

    /**
     * @param Template[] $templates
     * @return $this
     */
    public function setTemplates($templates)
    {
        foreach ($templates as $template) {
            $this->addTemplate($template);
        }
        return $this;
    }

    /**
     * @return Template[]
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * @param Template $template
     * @return $this
     */
    public function addTemplate(Template $template)
    {
        $this->templates[] = $template;
        return $this;
    }
}