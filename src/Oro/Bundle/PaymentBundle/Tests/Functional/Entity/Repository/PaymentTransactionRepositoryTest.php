<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;

/**
 * @dbIsolation
 */
class PaymentTransactionRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(['Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData']);
    }

    public function testGetPaymentMethods()
    {
        /** @var PaymentTransactionRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getRepository(PaymentTransaction::class);
        $id = 1;
        $result = $repository->getPaymentMethods(PaymentTransaction::class, [$id]);
        $this->assertCount($id, $result);
        $this->assertEquals([$id => ['payment_method']], $result);
    }
}
