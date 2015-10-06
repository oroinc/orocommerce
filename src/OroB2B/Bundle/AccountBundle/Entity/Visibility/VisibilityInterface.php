<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Visibility;

interface VisibilityInterface
{
    /**
     * @return string
     */
    public static function getDefault();

    /**
     * @param string $visibility
     * @return $this
     */
    public function setVisibility($visibility);

    /**
     * @return string
     */
    public function getVisibility();

    /**
     * @return array
     */
    public static function getVisibilityList();

    /**
     * @return object
     */
    public function getTargetEntity();

    /**
     * @param object $entity
     * @return $this
     */
    public function setTargetEntity($entity);
}
