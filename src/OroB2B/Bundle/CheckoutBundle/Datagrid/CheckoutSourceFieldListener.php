<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutSource\CheckoutSourceDefinerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CheckoutSourceFieldListener
{
    /**
     * @var CheckoutSourceDefinerInterface[]
     */
    private $definers = [];

    /**
     * @param RegistryInterface $doctrine
     */
    public function __construct(
        RegistryInterface $doctrine
    ) {
        $this->doctrine = $doctrine;
    }

    /**
     * @param CheckoutSourceDefinerInterface $definer
     */
    public function addSourceDefiner(CheckoutSourceDefinerInterface $definer)
    {
        $this->definers[] = $definer;
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

        foreach ($this->definers as $definer) {
            foreach ($definer->loadSources($em, $ids) as $id => $source) {
                foreach ($records as $record) {
                    if ($id == $record->getValue('id')) {
                        $record->addData(['source' => $source]);
                    }
                }
            }
        }
    }
}
