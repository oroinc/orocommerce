<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Oro\Bundle\VisibilityBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;

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
