<?php

namespace Oro\Bundle\CustomerBundle\EventListener;

use Oro\Bundle\CustomerBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;

class ProductDBQueryRestrictionEventListener
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
     * @param ProductDBQueryRestrictionEvent $event
     */
    public function onDBQuery(ProductDBQueryRestrictionEvent $event)
    {
        if ($this->frontendHelper->isFrontendRequest()) {
            $this->modifier->modify($event->getQueryBuilder());
        }
    }
}
