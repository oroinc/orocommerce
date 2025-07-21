<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Event\TransactionCompleteEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides functionality to work with payment transactions such as creating for current runtime, saving and fetching.
 */
class PaymentTransactionProvider
{
    use LoggerAwareTrait;

    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private CustomerUserProvider $customerUserProvider,
        private EventDispatcherInterface $dispatcher,
        private string $paymentTransactionClass
    ) {
    }

    /**
     * @param object $object
     * @param array $criteria
     * @param array $orderBy
     * @return PaymentTransaction|null
     */
    public function getPaymentTransaction(
        object $object,
        array $criteria = [],
        array $orderBy = []
    ): ?PaymentTransaction {
        $paymentTransactions = $this->getPaymentTransactions($object, $criteria, $orderBy, 1);

        return reset($paymentTransactions) ?: null;
    }

    /**
     * @return PaymentTransaction[]
     */
    public function getPaymentTransactions(
        object $object,
        array $criteria = [],
        array $orderBy = [],
        ?int $limit = null,
        ?int $offset = null
    ): array {
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
     * @return array|string[]
     */
    public function getPaymentMethods(object $object): array
    {
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($object);
        if (!$identifier) {
            return [];
        }
        $className = $this->doctrineHelper->getEntityClass($object);

        /** @var PaymentTransactionRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(PaymentTransaction::class);
        $methods = $repository->getPaymentMethods($className, [$identifier]);

        return $methods[$identifier] ?? [];
    }

    /**
     * @param object $object
     * @param float $amount
     * @param string $currency
     * @param string|null $paymentMethod
     * @return null|PaymentTransaction
     */
    public function getActiveAuthorizePaymentTransaction(
        object $object,
        float $amount,
        string $currency,
        ?string $paymentMethod = null
    ): ?PaymentTransaction {
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

    public function getActiveValidatePaymentTransaction(?string $paymentMethod): ?PaymentTransaction
    {
        $customerUser = $this->customerUserProvider->getLoggedUser(true);
        if (!$customerUser) {
            return null;
        }

        return $this->doctrineHelper->getEntityRepository($this->paymentTransactionClass)->findOneBy(
            [
                'active' => true,
                'successful' => true,
                'action' => PaymentMethodInterface::VALIDATE,
                'paymentMethod' => (string)$paymentMethod,
                'frontendOwner' => $customerUser,
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
    public function createPaymentTransaction(string $paymentMethod, string $type, $object): PaymentTransaction
    {
        $className = $this->doctrineHelper->getEntityClass($object);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($object);

        return $this->createEmptyPaymentTransaction()
            ->setPaymentMethod($paymentMethod)
            ->setAction($type)
            ->setEntityClass($className)
            ->setEntityIdentifier($identifier)
            ->setFrontendOwner($this->customerUserProvider->getLoggedUser(true));
    }

    public function createPaymentTransactionByParentTransaction(
        string $action,
        PaymentTransaction $parentPaymentTransaction
    ): PaymentTransaction {
        return $this->createEmptyPaymentTransaction()
            ->setAction($action)
            ->setPaymentMethod($parentPaymentTransaction->getPaymentMethod())
            ->setEntityClass($parentPaymentTransaction->getEntityClass())
            ->setEntityIdentifier($parentPaymentTransaction->getEntityIdentifier())
            ->setAmount($parentPaymentTransaction->getAmount())
            ->setCurrency($parentPaymentTransaction->getCurrency())
            ->setFrontendOwner($this->customerUserProvider->getLoggedUser(true))
            ->setSourcePaymentTransaction($parentPaymentTransaction);
    }

    /**
     * Finds the payment transaction with specified action and parent transaction. Creates a new one if not found.
     *
     * @param string $action
     * @param PaymentTransaction $parentPaymentTransaction
     * @param array<string,mixed> $criteria
     *
     * @return PaymentTransaction
     */
    public function findOrCreateByPaymentTransaction(
        string $action,
        PaymentTransaction $parentPaymentTransaction,
        array $criteria
    ): PaymentTransaction {
        $entity = $this->doctrineHelper->getEntityReference(
            $parentPaymentTransaction->getEntityClass(),
            $parentPaymentTransaction->getEntityIdentifier()
        );
        $paymentTransaction = $this->getPaymentTransaction($entity, ['action' => $action, ...$criteria]);

        if (!$paymentTransaction) {
            $paymentTransaction = $this->createPaymentTransactionByParentTransaction(
                $action,
                $parentPaymentTransaction
            );
        }

        return $paymentTransaction;
    }

    /**
     * @throws \Throwable
     */
    public function savePaymentTransaction(PaymentTransaction $paymentTransaction): void
    {
        $em = $this->doctrineHelper->getEntityManager($paymentTransaction);

        try {
            $em->wrapInTransaction(
                function (EntityManagerInterface $em) use ($paymentTransaction) {
                    if (!$paymentTransaction->getId()) {
                        $em->persist($paymentTransaction);
                    }
                }
            );

            $event = new TransactionCompleteEvent($paymentTransaction);
            $this->dispatcher->dispatch($event, TransactionCompleteEvent::NAME);
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->critical('Can not save payment transaction', ['exception' => $e]);
            }

            throw $e;
        }
    }

    private function createEmptyPaymentTransaction(): PaymentTransaction
    {
        return new $this->paymentTransactionClass();
    }
}
