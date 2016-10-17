<?php

namespace Oro\Bundle\AccountBundle\EventListener;

use Oro\Bundle\AccountBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\AccountBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;

class ProductDBQueryRestrictionEventListener
{
    /**
     * @var FrontendHelper
     */
    private $frontendHelper;

    /**
     * @var ProductVisibilityQueryBuilderModifier
     */
    private $dbModifier;

    /**
     * @var ProductVisibilitySearchQueryModifier
     */
    private $searchQueryModifier;

    /**
     * @param FrontendHelper                        $frontendHelper
     * @param ProductVisibilityQueryBuilderModifier $dbModifier
     * @param ProductVisibilitySearchQueryModifier  $searchQueryModifier
     */
    public function __construct(
        FrontendHelper $frontendHelper,
        ProductVisibilityQueryBuilderModifier $dbModifier,
        ProductVisibilitySearchQueryModifier $searchQueryModifier
    ) {
        $this->frontendHelper      = $frontendHelper;
        $this->dbModifier          = $dbModifier;
        $this->searchQueryModifier = $searchQueryModifier;
    }

    /**
     * @param ProductDBQueryRestrictionEvent $event
     */
    public function onDBQuery(ProductDBQueryRestrictionEvent $event)
    {
        if ($this->frontendHelper->isFrontendRequest()) {
            $this->dbModifier->modify($event->getQueryBuilder());
        }
    }

    /**
     * @param ProductSearchQueryRestrictionEvent $event
     */
    public function onSearchQuery(ProductSearchQueryRestrictionEvent $event)
    {
        if ($this->frontendHelper->isFrontendRequest()) {
            $this->searchQueryModifier->modify($event->getQuery());
        }
    }
}
