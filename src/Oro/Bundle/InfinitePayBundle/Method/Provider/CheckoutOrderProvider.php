<?php

namespace Oro\Bundle\InfinitePayBundle\Method\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Provider\AddressExtractor;

class CheckoutOrderProvider implements OrderProviderInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var AddressExtractor
     */
    protected $addressExtractor;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AddressExtractor $addressExtractor
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->addressExtractor = $addressExtractor;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return array|null
     */
    public function getDataObjectFromPaymentTransaction(PaymentTransaction $paymentTransaction)
    {
        $entity = $this->retrieveEntity($paymentTransaction);

        if (!$entity) {
            return null;
        }

        return $entity;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return object
     */
    private function retrieveEntity(PaymentTransaction $paymentTransaction)
    {
        $entity = $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );

        return $entity;
    }
}
