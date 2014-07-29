<?php
/**
 * @author tbedkowski
 */

namespace RstGroup\JsonApiPhp;

interface EntityInterface
{
    /**
     * @return string
     */
    public function getId();

    /**
     * @return array
     */
    public function toArray();
}