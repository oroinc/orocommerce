<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class AccountDataGridListener
{
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
}
