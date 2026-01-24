<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility;

use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * Defines the contract for visibility entities that control product and category visibility.
 *
 * Implementations of this interface represent visibility settings at different scopes (all customers,
 * customer groups, individual customers) and provide methods to manage visibility values, scope associations,
 * and target entities (products or categories). This is a core interface that enables the flexible visibility system
 * allowing merchants to control what products and categories are visible to different audiences.
 */
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
     * @return string
     */
    public static function getScopeType();

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
