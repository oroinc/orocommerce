<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility;

use Oro\Bundle\ScopeBundle\Entity\Scope;

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

    /**
     * @param Scope $scope
     * @return $this
     */
    public function setScope(Scope $scope);

    /**
     * @return Scope
     */
    public function getScope();
}
