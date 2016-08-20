<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Method\PaymentTerm;

/**
 * @dbIsolation
 */
class PaymentTransactionRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData']);
    }

    public function testGetPaymentMethods()
    {
        /** @var PaymentTransactionRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getRepository('OroPaymentBundle:PaymentTransaction');
        $result = $repository->getPaymentMethods('Oro\Bundle\PaymentBundle\Entity\PaymentTerm', [1]);
        $this->assertCount(1, $result);
        $this->assertEquals([1 => [PaymentTerm::TYPE]], $result);
    }
}
