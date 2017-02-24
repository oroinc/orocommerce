<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;

class PaymentTransactionRepositoryTest extends WebTestCase
{
    /**
     * @var PaymentTransactionRepository
     */
    private $repository;

    protected function setUp()
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
        $this->assertCount($id, $result);
        $this->assertEquals([$id => ['payment_method']], $result);
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
}
