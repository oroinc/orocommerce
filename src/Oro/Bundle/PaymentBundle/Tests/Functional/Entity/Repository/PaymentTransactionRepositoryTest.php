<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PaymentTransactionRepositoryTest extends WebTestCase
{
    private PaymentTransactionRepository $repository;

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
        self::assertCount($id, $result);
        self::assertEquals([$id => ['payment_method']], $result);
    }

    public function testFindByType()
    {
        $transactions = $this->repository->findByPaymentMethod(LoadPaymentTransactionData::PAYMENT_METHOD);

        self::assertContains(
            $this->getReference(LoadPaymentTransactionData::AUTHORIZE_ACTIVE_TRANSACTION),
            $transactions
        );
        self::assertContains($this->getReference(LoadPaymentTransactionData::AUTHORIZE_TRANSACTION), $transactions);
        self::assertContains($this->getReference(LoadPaymentTransactionData::VALIDATE_TRANSACTION), $transactions);
    }

    /**
     * @dataProvider findSuccessfulRelatedTransactionsByActionDataProvider
     */
    public function findSuccessfulRelatedTransactionsByAction(
        string $sourceTransactionReference,
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

        self::assertEquals($expectedTransactions, $actualRelatedTransactions);
    }

    public function findSuccessfulRelatedTransactionsByActionDataProvider(): array
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
