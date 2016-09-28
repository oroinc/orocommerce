<?php

namespace Oro\Bundle\ProductBundle\EventListener;

class ScopedProductDBQueryRestrictionEventListener extends ProductDBQueryRestrictionEventListener
{
    /**
     * @var string
     */
    protected $scope;

    /**
     * @param string $scope
     * @return $this
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * @return bool
     */
    protected function isConditionsAcceptable()
    {
        if (!$this->scope) {
            throw new \LogicException('Scope not configured for ProductDBQueryRestrictionEventListener');
        }

        return parent::isConditionsAcceptable() && $this->event->getDataParameters()->get('scope') === $this->scope;
    }
}
