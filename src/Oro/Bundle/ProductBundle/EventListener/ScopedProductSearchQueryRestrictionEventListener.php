<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Applies scope-specific restrictions to product search queries.
 *
 * Extends the base {@see ProductSearchQueryRestrictionEventListener} to add scope-based filtering, ensuring
 * that product search query restrictions are only applied when configured scope matches the current request context.
 */
class ScopedProductSearchQueryRestrictionEventListener extends ProductSearchQueryRestrictionEventListener
{
    /**
     * @var string
     */
    protected $scope;

    /**
     * @var RequestStack
     */
    protected $requestStack;

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
     * @param RequestStack $requestStack
     * @return $this
     */
    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;

        return $this;
    }

    /**
     * @return bool
     */
    #[\Override]
    protected function isConditionsAcceptable()
    {
        if (!$this->scope) {
            throw new \LogicException('Scope not configured for ProductSearchQueryRestrictionEventListener');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request || !$params = $request->get(ProductSelectType::DATA_PARAMETERS)) {
            return false;
        }

        return parent::isConditionsAcceptable() && $params['scope'] === $this->scope;
    }
}
