<?php

namespace Oro\Bundle\AccountBundle\EventListener;

use Oro\Bundle\AccountBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryModifierInterface;

class ProductSearchQueryRestrictionEventListener
{
    /**
     * @var QueryModifierInterface|ProductVisibilitySearchQueryModifier
     */
    private $searchQueryModifier;

    /**
     * @var FrontendHelper
     */
    private $frontendHelper;

    /**
     * @param FrontendHelper         $frontendHelper
     * @param QueryModifierInterface $searchQueryModifier
     */
    public function __construct(FrontendHelper $frontendHelper, QueryModifierInterface $searchQueryModifier)
    {
        $this->searchQueryModifier = $searchQueryModifier;
        $this->frontendHelper      = $frontendHelper;
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
