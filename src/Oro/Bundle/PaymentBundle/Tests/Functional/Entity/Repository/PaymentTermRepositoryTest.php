<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository;
use Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;

/**
 * @dbIsolation
 */
class PaymentTermRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(['Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTermData']);
    }

    public function testAccountPaymentTerm()
    {
        /** @var Account $account */
        $account = $this->getReference('account.level_1.2');

        $paymentTermName = LoadPaymentTermData::PAYMENT_TERM_REFERENCE_PREFIX . LoadPaymentTermData::TERM_LABEL_NET_20;

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getReference($paymentTermName);

        $this->assertTrue($paymentTerm->getAccounts()->contains($account));

        $this->assertEquals(
            $paymentTerm->getId(),
            $this->getRepository()->getOnePaymentTermByAccount($account)->getId()
        );

        $this->getRepository()->setPaymentTermToAccount($account);
        $this->getManager()->flush();

        $newPaymentTermName =
            LoadPaymentTermData::PAYMENT_TERM_REFERENCE_PREFIX . LoadPaymentTermData::TERM_LABEL_NET_10;

        /** @var PaymentTerm $newPaymentTerm */
        $newPaymentTerm = $this->getReference($newPaymentTermName);

        $this->getRepository()->setPaymentTermToAccount($account, $newPaymentTerm);
        $this->getManager()->flush();

        $this->assertFalse($paymentTerm->getAccounts()->contains($account));
        $this->assertTrue($newPaymentTerm->getAccounts()->contains($account));

        $this->assertEquals(
            $newPaymentTerm->getId(),
            $this->getRepository()->getOnePaymentTermByAccount($account)->getId()
        );
    }

    public function testAccountGroupPaymentTerm()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference('account_group.group1');

        $paymentTermName = LoadPaymentTermData::PAYMENT_TERM_REFERENCE_PREFIX . LoadPaymentTermData::TERM_LABEL_NET_10;

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getReference($paymentTermName);

        $this->assertTrue($paymentTerm->getAccountGroups()->contains($accountGroup));

        $this->assertEquals(
            $paymentTerm->getId(),
            $this->getRepository()->getOnePaymentTermByAccountGroup($accountGroup)->getId()
        );

        $this->getRepository()->setPaymentTermToAccountGroup($accountGroup);
        $this->getManager()->flush();

        $newPaymentTermName =
            LoadPaymentTermData::PAYMENT_TERM_REFERENCE_PREFIX . LoadPaymentTermData::TERM_LABEL_NET_40;

        /** @var PaymentTerm $newPaymentTerm */
        $newPaymentTerm = $this->getReference($newPaymentTermName);

        $this->getRepository()->setPaymentTermToAccountGroup($accountGroup, $newPaymentTerm);
        $this->getManager()->flush();

        $this->assertFalse($paymentTerm->getAccountGroups()->contains($accountGroup));
        $this->assertTrue($newPaymentTerm->getAccountGroups()->contains($accountGroup));

        $this->assertEquals(
            $newPaymentTerm->getId(),
            $this->getRepository()->getOnePaymentTermByAccountGroup($accountGroup)->getId()
        );
    }

    /**
     * @return PaymentTermRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroPaymentBundle:PaymentTerm');
    }

    /**
     * @return ObjectManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass('OroPaymentBundle:PaymentTerm');
    }
}
