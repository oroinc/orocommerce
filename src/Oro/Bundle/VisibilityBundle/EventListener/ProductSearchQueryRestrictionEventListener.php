<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryModifierInterface;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilitySearchQueryModifier;

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

    public function __construct(FrontendHelper $frontendHelper, QueryModifierInterface $searchQueryModifier)
    {
        $this->searchQueryModifier = $searchQueryModifier;
        $this->frontendHelper      = $frontendHelper;
    }

    public function onSearchQuery(ProductSearchQueryRestrictionEvent $event)
    {
        if ($this->frontendHelper->isFrontendRequest()) {
            $this->searchQueryModifier->modify($event->getQuery());
        }
    }
}
