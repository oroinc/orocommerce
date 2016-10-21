<?php

namespace Oro\Bundle\CustomerBundle\EventListener;

use Oro\Bundle\CustomerBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryBuilderModifierInterface;

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

    /**
     * @param FrontendHelper                $frontendHelper
     * @param QueryBuilderModifierInterface $modifier
     */
    public function __construct(FrontendHelper $frontendHelper, QueryBuilderModifierInterface $modifier)
    {
        $this->frontendHelper = $frontendHelper;
        $this->modifier       = $modifier;
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
