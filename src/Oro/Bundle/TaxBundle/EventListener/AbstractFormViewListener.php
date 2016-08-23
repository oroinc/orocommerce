<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

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

        $accountId = filter_var($request->get('id'), FILTER_VALIDATE_INT);
        if (false === $accountId) {
            return null;
        }

        return $this->doctrineHelper->getEntityReference($this->entityClass, $accountId);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    abstract public function onEdit(BeforeListRenderEvent $event);

    /**
     * @param BeforeListRenderEvent $event
     */
    abstract public function onView(BeforeListRenderEvent $event);
}
