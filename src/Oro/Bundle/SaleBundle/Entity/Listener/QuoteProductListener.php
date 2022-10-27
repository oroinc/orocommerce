<?php

namespace Oro\Bundle\SaleBundle\Entity\Listener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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
