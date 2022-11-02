<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryBuilderModifierInterface;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilityQueryBuilderModifier;

class ProductDBQueryRestrictionEventListener
{
    /**
     * @var FrontendHelper
     */
    private $frontendHelper;

    /**
     * @var QueryBuilderModifierInterface|ProductVisibilityQueryBuilderModifier
     */
    private $modifier;

    public function __construct(FrontendHelper $frontendHelper, QueryBuilderModifierInterface $modifier)
    {
        $this->frontendHelper = $frontendHelper;
        $this->modifier       = $modifier;
    }

    public function onDBQuery(ProductDBQueryRestrictionEvent $event)
    {
        if ($this->frontendHelper->isFrontendRequest()) {
            $this->modifier->modify($event->getQueryBuilder());
        }
    }
}
