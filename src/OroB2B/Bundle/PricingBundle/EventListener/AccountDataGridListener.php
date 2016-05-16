<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;

class AccountDataGridListener extends AbstractPriceListRelationDataGridListener
{
    /**
     * {@inheritdoc}
     */
    protected function addPriceListCondition(DatagridInterface $grid)
    {
        $params = $grid->getParameters();


        // todo: refactor
        if ($params->has('_minified')) {
            $filters = $params->get('_minified')['f'];
        } else {
            $filters = $params->get('_filter');
        }

        $priceLists = $filters[self::PRICE_LIST_KEY]['value'];
        unset($filters[self::PRICE_LIST_KEY]);
        if ($params->has('_minified')) {
            $minified = $params->get('_minified');
            $minified['f'] = $filters;
            $params->set('_minified', $minified);
        } else {
            $params->set('_filter', $filters);
        }

        $relationClass = 'OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount';

        $conditionFormat = 'EXISTS (SELECT 1 FROM %s r WHERE r.account = account AND IDENTITY(r.priceList) = %s)';
        $condition = sprintf($conditionFormat, $relationClass, join(', ', $priceLists));
        $grid->getConfig()->offsetAddToArrayByPath('[source][query][where][and]', [$condition]);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getRelations(array $priceListHolderIds)
    {
        return $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListToAccount')
            ->getRepository('OroB2BPricingBundle:PriceListToAccount')
            ->getRelationsByHolders($priceListHolderIds);
    }

    /**
     * {@inheritdoc}
     * @param PriceListToAccount $relation
     */
    protected function getObjectId(BasePriceListRelation $relation)
    {
        return $relation->getAccount()->getId();
    }
}
