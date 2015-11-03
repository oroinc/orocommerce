<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;
use OroB2B\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

class ScopedProductSelectDBQueryEventListener extends ProductSelectDBQueryEventListener
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
            throw new \LogicException('Scope not configured for ProductSelectDBQueryEventListener');
        }

        if (false === parent::isConditionsAcceptable()) {
            return false;
        }

        return $this->event->getDataParameters()->get('scope') === $this->scope;
    }
}
