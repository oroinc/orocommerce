<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use OroB2B\Bundle\AccountBundle\Model\ProductVisibilityQueryBuilderModifier;
use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;
use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;

class ProductSelectDBQueryEventListener
{
    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @var ProductVisibilityQueryBuilderModifier
     */
    protected $modifier;

    /**
     * @param FrontendHelper $frontendHelper
     * @param ProductVisibilityQueryBuilderModifier $modifier
     */
    public function __construct(
        FrontendHelper $frontendHelper,
        ProductVisibilityQueryBuilderModifier $modifier
    ) {
        $this->frontendHelper = $frontendHelper;
        $this->modifier = $modifier;
    }

    /**
     * @param ProductSelectDBQueryEvent $event
     */
    public function onDBQuery(ProductSelectDBQueryEvent $event)
    {
        if ($this->frontendHelper->isFrontendRequest()) {
            $this->modifier->modify($event->getQueryBuilder());
        }
    }
}
