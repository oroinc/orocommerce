<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid\ColumnResolver;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use OroB2B\Bundle\CheckoutBundle\Entity\Repository\BaseCheckoutRepository;

class ItemsCountColumnResolver implements ColumnResolverInterface
{
    /**
     * @var BaseCheckoutRepository
     */
    private $baseCheckoutRepository;

    /**
     * LineItemsCountColumnResolver constructor.
     * @param BaseCheckoutRepository $baseCheckoutRepository
     */
    public function __construct(BaseCheckoutRepository $baseCheckoutRepository)
    {
        $this->baseCheckoutRepository = $baseCheckoutRepository;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function resolveColumn(OrmResultAfter $event)
    {
        $ids = [];

        foreach ($event->getRecords() as $record) {
            $ids[] = $record->getValue('id');
        }

        $counts = $this->baseCheckoutRepository->countItemsByIds($ids);

        foreach ($event->getRecords() as $record) {
            if (isset($counts[$record->getValue('id')])) {
                $record->addData(['itemsCount' => $counts[$record->getValue('id')]]);
            }
        }
    }
}
