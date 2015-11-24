<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;

use OroB2B\Bundle\AccountBundle\Model\ProductVisibilityQueryBuilderModifier;
use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;
use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;

class ProductSelectDBQueryEventListener
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @var ProductVisibilityQueryBuilderModifier
     */
    protected $modifier;

    /**
     * @param RequestStack $requestStack
     * @param FrontendHelper $frontendHelper
     * @param ProductVisibilityQueryBuilderModifier $modifier
     */
    public function __construct(
        RequestStack $requestStack,
        FrontendHelper $frontendHelper,
        ProductVisibilityQueryBuilderModifier $modifier
    ) {
        $this->requestStack = $requestStack;
        $this->frontendHelper = $frontendHelper;
        $this->modifier = $modifier;
    }

    /**
     * @param ProductSelectDBQueryEvent $event
     */
    public function onDBQuery(ProductSelectDBQueryEvent $event)
    {
        if ($this->frontendHelper->isFrontendRequest($this->requestStack->getCurrentRequest())) {
            $this->modifier->modify($event->getQueryBuilder());
        }
    }
}
