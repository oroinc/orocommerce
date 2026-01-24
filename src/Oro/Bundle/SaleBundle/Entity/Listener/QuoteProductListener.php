<?php

namespace Oro\Bundle\SaleBundle\Entity\Listener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles quote product entity updates to preserve customer comments during quote updates.
 *
 * This listener ensures that when a quote is updated through the backend, the customer's comment field
 * is not overwritten with the old value, preserving any changes made to this field.
 */
class QuoteProductListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * QuoteProductListener constructor.
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function preUpdate(PreUpdateEventArgs $event)
    {
        $fieldToKeep = 'commentCustomer';
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }
        $route = $request->get('_route');

        if (($route === 'oro_sale_quote_update') && $event->hasChangedField($fieldToKeep)) {
            $event->setNewValue($fieldToKeep, $event->getOldValue($fieldToKeep));
        }
    }
}
