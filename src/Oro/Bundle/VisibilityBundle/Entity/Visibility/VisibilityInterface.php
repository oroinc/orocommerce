<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility;

interface VisibilityInterface
{
    const HIDDEN = 'hidden';
    const VISIBLE = 'visible';

    /**
     * @param object $target
     * @return string
     */
    public static function getDefault($target);

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
     * @param object $target
     * @return array
     */
    public static function getVisibilityList($target);

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
