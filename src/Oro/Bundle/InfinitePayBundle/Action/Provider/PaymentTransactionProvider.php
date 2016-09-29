<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PaymentTransactionProvider
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var string */
    protected $paymentTransactionClass;

    /**
     * @param string $paymentMethod
     * @param string $reference
     *
     * @return null|PaymentTransaction
     */
    public function getActivePurchasePaymentTransactionByReference($paymentMethod, $reference)
    {
        $accountUser = $this->getLoggedAccountUser();
        if (!$accountUser) {
            return null;
        }

        return $this->doctrineHelper->getEntityRepository($this->paymentTransactionClass)->findOneBy(
            [
                'active' => true,
                'successful' => false,
                'action' => PaymentMethodInterface::PURCHASE,
                'paymentMethod' => (string) $paymentMethod,
                'reference' => $reference,
            ],
            ['id' => Criteria::DESC]
        );
    }

    /**
     * @return AccountUser|null
     */
    protected function getLoggedAccountUser()
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return null;
        }

        $user = $token->getUser();

        if ($user instanceof AccountUser) {
            return $user;
        }

        return null;
    }
}
