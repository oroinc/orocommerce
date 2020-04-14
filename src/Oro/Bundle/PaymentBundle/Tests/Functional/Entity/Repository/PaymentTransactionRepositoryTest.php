<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PaymentTransactionRepositoryTest extends WebTestCase
{
    /**
     * @var PaymentTransactionRepository
     */
    private $repository;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadPaymentTransactionData::class]);

        $this->repository = $this->getContainer()->get('doctrine')->getRepository(PaymentTransaction::class);
    }

    public function testGetPaymentMethods()
    {
        $id = 1;
        $result = $this->repository->getPaymentMethods(PaymentTransaction::class, [$id]);
        static::assertCount($id, $result);
        static::assertEquals([$id => ['payment_method']], $result);
    }

    public function testFindByType()
    {
        $transactions = $this->repository->findByPaymentMethod(LoadPaymentTransactionData::PAYMENT_METHOD);

        static::assertContains(
            $this->getReference(LoadPaymentTransactionData::AUTHORIZE_ACTIVE_TRANSACTION),
            $transactions
        );
        static::assertContains($this->getReference(LoadPaymentTransactionData::AUTHORIZE_TRANSACTION), $transactions);
        static::assertContains($this->getReference(LoadPaymentTransactionData::VALIDATE_TRANSACTION), $transactions);
    }

    /**
     * @param string $sourceTransactionReference
     * @param array  $expectedRelatedTransactionsReferences
     *
     * @dataProvider findSuccessfulRelatedTransactionsByActionDataProvider
     */
    public function findSuccessfulRelatedTransactionsByAction(
        $sourceTransactionReference,
        array $expectedRelatedTransactionsReferences
    ) {
        $authorizationTransaction = $this->getReference($sourceTransactionReference);

        $actualRelatedTransactions = $this->repository->findSuccessfulRelatedTransactionsByAction(
            $authorizationTransaction,
            PaymentMethodInterface::CHARGE
        );

        $expectedTransactions = [];
        foreach ($expectedRelatedTransactionsReferences as $expectedRelatedTransactionReference) {
            $expectedTransactions[] = $this->getReference($expectedRelatedTransactionReference);
        }

        static::assertEquals($expectedTransactions, $actualRelatedTransactions);
    }

    /**
     * @return array
     */
    public function findSuccessfulRelatedTransactionsByActionDataProvider()
    {
        return [
            [
                'source_transaction_reference' => LoadPaymentTransactionData::CHARGED_AUTHORIZE_TRANSACTION,
                'expected_transactions_references' => [
                    LoadPaymentTransactionData::CHARGE_TRANSACTION,
                ],
            ],
            [
                'source_transaction_reference' => LoadPaymentTransactionData::AUTHORIZE_TRANSACTION,
                'expected_transactions_references' => [],
            ],
        ];
    }
}
