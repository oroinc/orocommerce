<?php

namespace OroB2B\Bundle\PaymentBundle\Provider;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class PaymentTransactionProvider
{
    use LoggerAwareTrait;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $paymentTransactionClass;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param TokenStorageInterface $tokenStorage
     * @param string $paymentTransactionClass
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        TokenStorageInterface $tokenStorage,
        $paymentTransactionClass
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->paymentTransactionClass = $paymentTransactionClass;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param object $object
     * @param array $criteria
     * @param array $orderBy
     * @return PaymentTransaction|null
     */
    public function getPaymentTransaction($object, array $criteria = [], array $orderBy = [])
    {
        $paymentTransactions = $this->getPaymentTransactions($object, $criteria, $orderBy, 1);

        return reset($paymentTransactions);
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

    /**
     * @param object $object
     * @param array $criteria
     * @param array $orderBy
     * @param int $limit
     * @param int $offset
     * @return PaymentTransaction[]
     */
    public function getPaymentTransactions(
        $object,
        array $criteria = [],
        array $orderBy = [],
        $limit = null,
        $offset = null
    ) {
        $className = $this->doctrineHelper->getEntityClass($object);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($object);

        return $this->doctrineHelper->getEntityRepository($this->paymentTransactionClass)->findBy(
            array_merge(
                $criteria,
                [
                    'entityClass' => $className,
                    'entityIdentifier' => $identifier,
                ]
            ),
            array_merge(['id' => Criteria::DESC], $orderBy),
            $limit,
            $offset
        );
    }

    /**
     * @param object $object
     * @param float $amount
     * @param string $currency
     * @param string|null $paymentMethod
     * @return null|PaymentTransaction
     */
    public function getActiveAuthorizePaymentTransaction($object, $amount, $currency, $paymentMethod = null)
    {
        $criteria = [
            'active' => true,
            'successful' => true,
            'amount' => (string)round($amount, 2),
            'currency' => $currency,
            'action' => PaymentMethodInterface::AUTHORIZE,
        ];

        if ($paymentMethod) {
            $criteria['paymentMethod'] = (string)$paymentMethod;
        }

        return $this->getPaymentTransaction($object, $criteria);
    }

    /**
     * @param string $paymentMethod
     * @return PaymentTransaction
     */
    public function getActiveValidatePaymentTransaction($paymentMethod)
    {
        $accountUser = $this->getLoggedAccountUser();
        if (!$accountUser) {
            return [];
        }

        return $this->doctrineHelper->getEntityRepository($this->paymentTransactionClass)->findOneBy(
            [
                'active' => true,
                'successful' => true,
                'action' => PaymentMethodInterface::VALIDATE,
                'paymentMethod' => (string)$paymentMethod,
                'frontendOwner' => $accountUser,
            ],
            ['id' => Criteria::DESC]
        );
    }

    /**
     * @param string $paymentMethod
     * @param string $type
     * @param object $object
     * @return PaymentTransaction
     */
    public function createPaymentTransaction($paymentMethod, $type, $object)
    {
        $className = $this->doctrineHelper->getEntityClass($object);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($object);

        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = new $this->paymentTransactionClass;
        $paymentTransaction
            ->setPaymentMethod($paymentMethod)
            ->setAction($type)
            ->setEntityClass($className)
            ->setEntityIdentifier($identifier)
            ->setFrontendOwner($this->getLoggedAccountUser());

        return $paymentTransaction;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    public function savePaymentTransaction(PaymentTransaction $paymentTransaction)
    {
        $em = $this->doctrineHelper->getEntityManager($paymentTransaction);
        try {
            $em->transactional(
                function (EntityManagerInterface $em) use ($paymentTransaction) {
                    if (!$paymentTransaction->getId()) {
                        $em->persist($paymentTransaction);
                    }
                }
            );
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error($e->getMessage(), $e->getTrace());
            }
        }
    }

    /**
     * @param array $criteria
     * @return PaymentTransaction
     */
    public function findOneBy(array $criteria)
    {
        return $this->doctrineHelper->getEntityRepository($this->paymentTransactionClass)->findOneBy(
            $criteria,
            ['id' => Criteria::DESC]
        );
    }
}
