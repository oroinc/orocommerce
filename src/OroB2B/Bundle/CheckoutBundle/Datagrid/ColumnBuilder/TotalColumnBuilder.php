<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid\ColumnBuilder;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

use OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class TotalColumnBuilder implements ColumnBuilderInterface
{
    /**
     * @param RegistryInterface      $doctrine
     * @param TotalProcessorProvider $totalProcessor
     */
    public function __construct(
        RegistryInterface $doctrine,
        TotalProcessorProvider $totalProcessor
    ) {
        $this->doctrine       = $doctrine;
        $this->totalProcessor = $totalProcessor;
    }

    /**
     * @param ResultRecord[] $records
     */
    public function buildColumn($records)
    {
        $em = $this->doctrine->getRepository(BaseCheckout::class);
        // todo: Reduce db queries count
        foreach ($records as $record) {
            if (!$record->getValue('total')) {
                $id = $record->getValue('id');
                $ch = $em->find($id);

                $sourceEntity = $ch->getSourceEntity();
                $record->addData(
                    [
                        'total' => $this->totalProcessor
                            ->getTotal($sourceEntity)
                            ->getAmount()
                    ]
                );
            }
        }
    }
}
