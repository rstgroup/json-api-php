<?php
/**
 * @author tbedkowski
 */

namespace RstGroup\JsonApiPhp;

class Template
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $href;

    /**
     * @var string
     */
    protected $type;

    function __construct($key, $href, $type = null)
    {
        $this->key = $key;
        $this->href = $href;
        $this->type = $type;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
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
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}