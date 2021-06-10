<?php

namespace Oro\Bundle\TaxBundle\EventListener\OrderTax;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TaxBundle\Model\Taxable;

/**
 * Handles skip order tax recalculation event.
 */
abstract class AbstractTaxableListener
{
    protected ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    protected function getUnitOfWork(Taxable $taxable): ?UnitOfWork
    {
        /** @var EntityManager|null $entityManager */
        $entityManager = $this->doctrine->getManagerForClass($taxable->getClassName());
        if (!$entityManager) {
            return null;
        }

        return $entityManager->getUnitOfWork();
    }
}
