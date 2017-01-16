<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\ProductBundle\Provider\ConfigurableProductProvider;

class LineItemsFrontendDatagridListener
{
    /**
     * @var ConfigurableProductProvider $configurableProductProvider
     */
    protected $configurableProductProvider;

    /**
     * LineItemsFrontendDatagridListener constructor.
     * @param ConfigurableProductProvider $configurableProductProvider
     */
    public function __construct(ConfigurableProductProvider $configurableProductProvider)
    {
        $this->configurableProductProvider = $configurableProductProvider;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        foreach ($records as $record) {
            $products = $this->configurableProductProvider->getLineItemProduct($record->getRootEntity());
            if ($products) {
                $record->addData($products);
            }
        }
    }
}
