<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

class AccountDataGridListener
{
    protected $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        /**
         * todo: add condition
         *  SELECT a.id, a.name FROM orob2b_account a WHERE EXISTS(
         *    SELECT 1 FROM orob2b_price_list_to_account r WHERE r.account_id = a.id
         *  );
         **/

        $grid = $event->getDatagrid();
        $parameters = $grid->getParameters();
    }

    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        $accountIds = [];

        foreach ($records as $record) {
            $accountIds[] = $record->getValue('id');
        }

        if (!empty($accountIds)) {
            $groupedPriceLists = [];
            $relations = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListToAccount')
                ->getRepository('OroB2BPricingBundle:PriceListToAccount')
                ->getRelationsByAccounts($accountIds);

            foreach ($relations as $relation) {
                //TODO: use website in BB-3131
//                $groupedPriceLists[$relation->getAccount()->getId()][$relation->getWebsite()->getId()]['website']
//                    = $relation->getWebsite();
                $groupedPriceLists[$relation->getAccount()->getId()][$relation->getWebsite()->getId()]['priceLists'][]
                    = $relation->getPriceList()->getName();
            }

            foreach ($records as $record) {
                $accountId = $record->getValue('id');
                if (array_key_exists($accountId, $groupedPriceLists)) {
                    //TODO: will be removed in BB-3131
                    $implode = '';
                    foreach ($groupedPriceLists[$accountId] as $priceListsName) {
                        $implode .= implode(', ', $priceListsName['priceLists']); // demo string, remove at BB-3131
                    }

                    $data = ['price_lists' => $implode];
                    $record->addData($data);
                }
            }
        }
    }
}
