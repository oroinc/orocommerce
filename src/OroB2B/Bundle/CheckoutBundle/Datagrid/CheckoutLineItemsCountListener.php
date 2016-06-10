<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutItemsCounters\CheckoutItemsCounterInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * This listener counts items in orders and adds the result to the grid data.
 * It either counts items from shopping list which order was created from, or the quote.
 *
 * Class CheckoutLineItemsCountListener
 * @package OroB2B\Bundle\CheckoutBundle\Datagrid
 */
class CheckoutLineItemsCountListener
{
    /**
     * @var CheckoutItemsCounterInterface[]
     */
    private $counters = [];
    
    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @param RegistryInterface $doctrine
     */
    public function __construct(
        RegistryInterface $doctrine
    ) {
        $this->doctrine = $doctrine;
    }

    /**
     * @param CheckoutItemsCounterInterface $counter
     */
    public function addCounter(CheckoutItemsCounterInterface $counter)
    {
        $this->counters[] = $counter;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        $em = $this->doctrine->getEntityManagerForClass(
            'OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout'
        );

        $ids = [];

        foreach ($records as $record) {
            $ids[] = $record->getValue('id');
        }

        foreach ($this->counters as $counter) {
            foreach ($counter->countItems($em, $ids) as $id => $count) {
                foreach ($records as $record) {
                    if ($id == $record->getValue('id')) {
                        $record->addData(['itemsCount' => $count]);
                    }
                }
            }
        }
    }
}
