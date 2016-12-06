<?php

namespace Oro\Bundle\SaleBundle\Entity\Listener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

class QuoteProductListener
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PreUpdateEventArgs $event)
    {
        $fieldToKeep = 'commentAccount';
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }
        $route = $request->get('_route');

        if (($route == 'oro_sale_quote_update') && $event->hasChangedField($fieldToKeep)) {
            $event->setNewValue($fieldToKeep, $event->getOldValue($fieldToKeep));
        }
    }
}
