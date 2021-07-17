<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractFormViewListener
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var RequestStack */
    protected $requestStack;

    /** @var string */
    protected $taxCodeClass;

    /** @var string */
    protected $entityClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param RequestStack $requestStack
     * @param string $taxCodeClass
     * @param string $entityClass
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        RequestStack $requestStack,
        $taxCodeClass,
        $entityClass
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->requestStack = $requestStack;
        $this->taxCodeClass = $taxCodeClass;
        $this->entityClass = $entityClass;
    }

    /**
     * @return null|object
     */
    protected function getEntityFromRequest()
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        $customerId = filter_var($request->get('id'), FILTER_VALIDATE_INT);
        if (false === $customerId) {
            return null;
        }

        return $this->doctrineHelper->getEntityReference($this->entityClass, $customerId);
    }

    abstract public function onEdit(BeforeListRenderEvent $event);

    abstract public function onView(BeforeListRenderEvent $event);
}
