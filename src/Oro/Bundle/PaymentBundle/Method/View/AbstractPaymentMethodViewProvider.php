<?php

namespace Oro\Bundle\PaymentBundle\Method\View;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Abstract base provider for managing
 * and retrieving payment method view representations with lazy loading support
 */
abstract class AbstractPaymentMethodViewProvider implements PaymentMethodViewProviderInterface
{
    /** @var ArrayCollection|PaymentMethodViewInterface[] */
    protected $views;

    /**
     * @return ArrayCollection|PaymentMethodViewInterface[]
     */
    abstract protected function buildViews();

    public function __construct()
    {
        $this->views = new ArrayCollection();
    }

    #[\Override]
    public function hasPaymentMethodView($identifier)
    {
        return $identifier ? $this->getViews()->containsKey($identifier) : false;
    }

    #[\Override]
    public function getPaymentMethodView($identifier)
    {
        if (!$this->hasPaymentMethodView($identifier)) {
            return null;
        }

        return $this->getViews()->get($identifier);
    }

    #[\Override]
    public function getPaymentMethodViews(array $identifiers)
    {
        $views = [];
        foreach ($identifiers as $identifier) {
            if ($this->hasPaymentMethodView($identifier)) {
                $views[] = $this->getPaymentMethodView($identifier);
            }
        }

        return $views;
    }

    /**
     * @return ArrayCollection|PaymentMethodViewInterface[]
     */
    protected function getViews()
    {
        if ($this->views->isEmpty()) {
            $this->buildViews();
        }

        return $this->views;
    }

    /**
     * @param string $identifier
     * @param PaymentMethodViewInterface $view
     */
    protected function addView($identifier, PaymentMethodViewInterface $view)
    {
        $this->views->set($identifier, $view);
    }
}
