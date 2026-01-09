<?php

namespace Oro\Bundle\ProductBundle\EventListener;

/**
 * Applies scope-specific restrictions to product database queries.
 *
 * Extends the base {@see ProductDBQueryRestrictionEventListener} to add scope-based filtering,
 * ensuring that product query restrictions are only applied when the configured scope matches
 * the current request context.
 */
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

        return $this;
    }

    /**
     * @return bool
     */
    #[\Override]
    protected function isConditionsAcceptable()
    {
        if (!$this->scope) {
            throw new \LogicException('Scope not configured for ProductDBQueryRestrictionEventListener');
        }

        return parent::isConditionsAcceptable() && $this->event->getDataParameters()->get('scope') === $this->scope;
    }
}
