<?php

namespace Oro\Bundle\AccountBundle\EventListener;

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
     * @var QueryBuilderModifierInterface
     */
    private $dbModifier;

    /**
     * @param FrontendHelper                $frontendHelper
     * @param QueryBuilderModifierInterface $dbModifier
     */
    public function __construct(FrontendHelper $frontendHelper, QueryBuilderModifierInterface $dbModifier)
    {
        $this->frontendHelper = $frontendHelper;
        $this->dbModifier     = $dbModifier;
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
}
