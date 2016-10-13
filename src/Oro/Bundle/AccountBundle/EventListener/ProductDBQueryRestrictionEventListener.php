<?php

namespace Oro\Bundle\AccountBundle\EventListener;

use Oro\Bundle\AccountBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;

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

    /**
     * @param ProductSearchQueryRestrictionEvent $event
     */
    public function onSearchQuery(ProductSearchQueryRestrictionEvent $event)
    {
        if ($this->frontendHelper->isFrontendRequest()) {
            $this->modifier->modifySearch($event->getQuery());
        }
    }
}
