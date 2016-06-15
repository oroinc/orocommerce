<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutSource\CheckoutSourceDefinitionResolverInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CheckoutSourceFieldListener
{
    /**
     * @var CheckoutSourceDefinitionResolverInterface[]
     */
    private $definitionResolvers = [];

    /**
     * @param RegistryInterface $doctrine
     */
    public function __construct(
        RegistryInterface $doctrine
    ) {
        $this->doctrine = $doctrine;
    }

    /**
     * @param CheckoutSourceDefinitionResolverInterface $definitionResolver
     */
    public function addSourceDefinitionResolver(CheckoutSourceDefinitionResolverInterface $definitionResolver)
    {
        $this->definitionResolvers[] = $definitionResolver;
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

        foreach ($this->definitionResolvers as $definitionResolver) {
            foreach ($definitionResolver->loadSources($em, $ids) as $id => $source) {
                foreach ($records as $record) {
                    if ($id == $record->getValue('id')) {
                        $record->addData(['startedFrom' => $source]);
                    }
                }
            }
        }
    }
}
