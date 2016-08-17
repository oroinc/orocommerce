<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use OroB2B\Bundle\PaymentBundle\Method\PaymentTerm;

/**
 * @dbIsolation
 */
class PaymentTransactionRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData']);
    }

    public function testGetPaymentMethods()
    {
        /** @var PaymentTransactionRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getRepository('OroB2BPaymentBundle:PaymentTransaction');
        $result = $repository->getPaymentMethods('OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm', [1]);
        $this->assertCount(1, $result);
        $this->assertEquals([1 => [PaymentTerm::TYPE]], $result);
    }
}
