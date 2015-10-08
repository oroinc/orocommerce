<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Visibility;

interface VisibilityInterface
{
    /**
     * @param object|null $target
     * @return string
     */
    public static function getDefault($target = null);

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
     * @param object|null $target
     * @return array
     */
    public static function getVisibilityList($target = null);

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
