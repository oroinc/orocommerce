<?php

namespace Oro\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;

class PaymentMethodConfigRepository extends EntityRepository
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
