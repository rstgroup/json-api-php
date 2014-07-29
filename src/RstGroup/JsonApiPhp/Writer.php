<?php
/**
 * @author tbedkowski
 */

namespace RstGroup\JsonApiPhp;

class Writer
{
    const AS_ID     = 'id';
    const AS_OBJECT = 'object';
    const AS_OBJECTS_ARRAY  = 'objectsArray';

    /**
     * @var string
     */
    protected $linkForm = self::AS_OBJECT;

    /**
     * @var bool
     */
    protected $templatesLinksAsObject = false;

    /**
     * @var bool
     */
    protected $attachDocumentHref = true;

    /**
     * @var bool
     */
    protected $attachDocumentLinks = true;

    /**
     * @var bool
     */
    protected $attachTemplates = true;

    /**
     * @var bool
     */
    protected $attachLinked = true;

    /**
     * @var array
     */
    protected $result;

    /**
     * @var string
     */
    protected $collectionName;

    /**
     * @var string
     */
    protected $href;

    /**
     * @var EntityInterface[]
     */
    protected $entities = array();

    /**
     * @var Relation[]
     */
    protected $relations = array();

    /**
     * @var Template[]
     */
    protected $templates = array();

    /**
     * @param string $linkForm
     * @return $this
     */
    public function setLinkForm($linkForm)
    {
        $this->linkForm = $linkForm;
        return $this;
    }

    /**
     * @param boolean $attach
     * @return $this
     */
    public function setAttachDocumentHref($attach)
    {
        $this->attachDocumentHref = (bool) $attach;
        return $this;
    }

    /**
     * @param boolean $templatesLinksAsObject
     * @return $this
     */
    public function setTemplatesLinksAsObject($templatesLinksAsObject)
    {
        $this->templatesLinksAsObject = (bool) $templatesLinksAsObject;
        return $this;
    }

    /**
     * @param boolean $attach
     * @return $this
     */
    public function setAttachDocumentLinks($attach)
    {
        $this->attachDocumentLinks = (bool) $attach;
        return $this;
    }

    /**
     * @param boolean $attach
     * @return $this
     */
    public function setAttachTemplates($attach)
    {
        $this->attachTemplates = (bool) $attach;
        return $this;
    }

    /**
     * @param boolean $attach
     * @return $this
     */
    public function setAttachLinked($attach)
    {
        $this->attachLinked = (bool) $attach;
        return $this;
    }

    /**
     * @param \RstGroup\JsonApiPhp\Resource $resource
     * @return array
     */
    public function write(Resource $resource)
    {
        $this->init($resource);

        $this->attachLinks();
        $this->attachDocuments();
        $this->attachLinked();

        return $this->normalizeResult();
    }

    /**
     * @param \RstGroup\JsonApiPhp\Resource $resource
     */
    protected function init(Resource $resource)
    {
        $this->collectionName = $resource->getCollectionName();
        $this->href = $resource->getHref();
        $this->entities = $resource->getEntities();
        $this->relations = $resource->getRelations();
        $this->templates = $resource->getTemplates();

        $this->result = array(
            'links' => array(),
            $this->collectionName => array(),
            'linked' => array(),
        );
    }

    /**
     * Attaches links (templates) to result.
     */
    protected function attachLinks()
    {
        if (!$this->attachTemplates) {
            return;
        }

        $templatesLinks = array();
        foreach ($this->templates as $template) {
            $link = ($this->templatesLinksAsObject)
                ? array(
                    'href' => $template->getHref(),
                    'type' => $template->getType(),
                ) : $template->getHref();

            $templatesLinks += array($template->getKey() => $link);
        }
        $this->result['links'] = $templatesLinks;
    }

    /**
     * Attaches resource documents to result.
     */
    protected function attachDocuments()
    {
        foreach ($this->entities as $entity) {
            $this->result[$this->collectionName][] = $this->renderDocument($entity);
        }
    }

    /**
     * Attaches linked documents to result.
     */
    protected function attachLinked()
    {
        if (!$this->attachLinked) {
            return;
        }

        foreach ($this->entities as $entity) {
            foreach ($this->relations as $relation) {
                $name = ($this->isToManyRelation($relation))
                    ? $relation->getCollectionName()
                    : $relation->getName();

                $collectionName = $relation->getCollectionName();

                $linked = (isset($this->result['linked'][$collectionName]))
                    ? $this->result['linked'][$collectionName]
                    : array();

                $subEntities = $this->getSubEntities($entity, $name);
                $this->result['linked'][$collectionName] = $this->renderLinkedDocuments($linked, $entity, $subEntities, $relation);
            }
        }
    }

    /**
     * Renders resource document.
     * @param EntityInterface $entity
     * @return array
     */
    protected function renderDocument(EntityInterface $entity)
    {
        $document = array();
        $document['id'] = (string) $entity->getId();

        if ($this->attachDocumentHref) {
            $binds = array($this->collectionName => $entity->getId());
            $href = $this->prepareHref($this->href, $binds);
            $document['href'] = $href;
        }

        $document += $entity->toArray();

        if ($this->attachDocumentLinks && !empty($this->relations)) {
            $document['links'] = $this->renderLinks($entity);
        }

        return $document;
    }

    /**
     * Renders resource documents links array.
     * @param EntityInterface $entity
     * @return array
     */
    protected function renderLinks(EntityInterface $entity)
    {
        $links = array();
        foreach ($this->relations as $relation) {
            $type = $relation->getCollectionName();
            $name = ($this->isToManyRelation($relation))
                ? $type
                : $relation->getName();

            $href  = $relation->getHref();
            $ids   = $this->extractSubEntitiesIds($entity, $name);
            $binds = array($this->collectionName => $entity->getId());

            if ($this->isToOneRelation($relation) || $this->linkForm !== self::AS_OBJECTS_ARRAY) {
                $links[$name] = $this->renderLink($relation->getRelType(), $ids, $href, $binds, $type);
                continue;
            }

            if ($this->isToManyRelation($relation)) {
                foreach ($ids as $id) {
                    $links[$name][] = $this->renderLink(Relation::TO_ONE, $id, $href, $binds, $type);
                }
            }
        }

        return $links;
    }

    /**
     * Renders resource document link.
     * @param string $relType
     * @param int|int[] $id
     * @param string $href
     * @param string[] $binds
     * @param string $type
     * @return array|mixed
     */
    protected function renderLink($relType, $id, $href, array $binds, $type)
    {
        $idKey   = ($relType === Relation::TO_ONE) ? 'id' : 'ids';
        $idValue = ($relType === Relation::TO_ONE && is_array($id)) ? reset($id) : $id;

        if ($this->linkForm === self::AS_ID) {
            return $idValue;
        }

        return array(
            $idKey => $idValue,
            'href' => $this->prepareHref($href, $binds + array($type => $id)),
            'type' => $type,
        );
    }

    /**
     * Renders linked documents array.
     * @param array $linked
     * @param EntityInterface $entity
     * @param EntityInterface[] $subEntities
     * @param Relation $relation
     * @return array
     */
    protected function renderLinkedDocuments(array &$linked, EntityInterface $entity, array $subEntities, Relation $relation)
    {
        foreach ($subEntities as $subEntity) {
            $id = $subEntity->getId();

            if (isset($linked[$id])) {
                continue;
            }

            $linked[$id] = $this->renderLinkedDocument($entity, $subEntity, $relation);
        }
        return $linked;
    }

    /**
     * Renders linked document.
     * @param EntityInterface $entity
     * @param EntityInterface $subEntity
     * @param Relation $relation
     * @return array
     */
    protected function renderLinkedDocument(EntityInterface $entity, EntityInterface $subEntity, Relation $relation)
    {
        $document = array();
        $document['id'] = (string) $subEntity->getId();

        if ($this->attachDocumentHref) {
            $binds = array(
                $this->collectionName => $entity->getId(),
                $relation->getCollectionName() => $subEntity->getId(),
            );
            $href = $this->prepareHref($relation->getHref(), $binds);
            $document['href'] = $href;
        }

        $document += $subEntity->toArray();

        return $document;
    }

    /**
     * Retrieves sub-entities of given entity.
     * @param EntityInterface $entity
     * @param string $name
     * @return EntityInterface[]
     */
    protected function getSubEntities(EntityInterface $entity, $name)
    {
        $method = 'get' . ucfirst($name);
        $subEntities = $entity->{$method}();

        if (!is_array($subEntities)) {
            $subEntities = array($subEntities);
        }
        return $subEntities;
    }

    /**
     * Retrieves sub-entities ids of given entity.
     * @param EntityInterface $entity
     * @param string $name
     * @return array
     */
    protected function extractSubEntitiesIds(EntityInterface $entity, $name)
    {
        $subEntities = $this->getSubEntities($entity, $name);

        $ids = array();
        foreach ($subEntities as $subEntity) {
            $ids[] = (string) $subEntity->getId();
        }
        return $ids;
    }

    /**
     * @param Relation $relation
     * @return bool
     */
    protected function isToManyRelation(Relation $relation)
    {
        return ($relation->getRelType() === Relation::TO_MANY);
    }

    /**
     * @param Relation $relation
     * @return bool
     */
    protected function isToOneRelation(Relation $relation)
    {
        return ($relation->getRelType() === Relation::TO_ONE);
    }

    /**
     * @param string $href
     * @param array $binds
     * @return string
     */
    protected function prepareHref($href, array $binds)
    {
        $bindKeys = $this->extractBindKeys($href);
        $binds = $this->prepareBinds($bindKeys, $binds);
        return $this->bind($binds, $href);
    }

    /**
     * @param string $href
     * @return array
     */
    protected function extractBindKeys($href)
    {
        $pattern = '/(\{[a-zA-Z0-9-.]*\})/';
        if (preg_match_all($pattern, $href, $matches)) {
            return array_fill_keys($matches[0], null);
        }
        return array();
    }

    /**
     * @param array $bindKeys
     * @param array $ids
     * @return array
     */
    protected function prepareBinds(array $bindKeys, array $ids)
    {
        foreach ($ids as $key => $values) {
            $bindKey = '{' . $key . '.id}';
            if (array_key_exists($bindKey, $bindKeys)) {
                $bindKeys[$bindKey] = $values;
            }
        }
        return $bindKeys;
    }

    /**
     * @param array $binds
     * @param string $string
     * @return string
     */
    protected function bind(array $binds, $string)
    {
        foreach ($binds as $bind => $value) {
            if ($value === null) {
                continue;
            }
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $string = str_replace($bind, $value, $string);
        }
        return $string;
    }

    /**
     * @param array $linked
     */
    protected function renumberKeys(array &$linked)
    {
        foreach ($linked as &$collection) {
            $collection = array_values($collection);
        }
    }

    protected function normalizeResult()
    {
        if (empty($this->result[$this->collectionName])) {
            return array();
        }

        if (empty($this->result['links'])) {
            unset($this->result['links']);
        }

        if (empty($this->result['linked'])) {
            unset($this->result['linked']);
        } else {
            $this->renumberKeys($this->result['linked']);
        }

        return $this->result;
    }
}