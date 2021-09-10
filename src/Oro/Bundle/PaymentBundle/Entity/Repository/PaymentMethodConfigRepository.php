<?php

namespace Oro\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;

/**
 * Doctrine repository for PaymentMethodConfig entity
 */
class PaymentMethodConfigRepository extends ServiceEntityRepository
{
    /**
     * @param string|string[] $type
     *
     * @return PaymentMethodConfig[]
     */
    public function findByType($type)
    {
        return $this->findBy([
            'type' => $type
        ]);
    }
}
