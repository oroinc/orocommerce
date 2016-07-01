<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid\ColumnBuilder;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

use OroB2B\Bundle\CheckoutBundle\Entity\Repository\BaseCheckoutRepository;

class ItemsCountColumnBuilder implements ColumnBuilderInterface
{
    /**
     * @var BaseCheckoutRepository
     */
    protected $baseCheckoutRepository;

    /**
     * @param BaseCheckoutRepository $baseCheckoutRepository
     */
    public function __construct(BaseCheckoutRepository $baseCheckoutRepository)
    {
        $this->baseCheckoutRepository = $baseCheckoutRepository;
    }

    /**
     * @param ResultRecord[] $records
     */
    public function buildColumn($records)
    {
        $ids = [ ];

        foreach ($records as $record) {
            $ids[] = $record->getValue('id');
        }

        $counts = $this->baseCheckoutRepository->countItemsPerCheckout($ids);

        foreach ($records as $record) {
            if (isset($counts[$record->getValue('id')])) {
                $record->addData([ 'itemsCount' => $counts[$record->getValue('id')] ]);
            }
        }
    }
}
