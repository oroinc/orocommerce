<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller\Frontend;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolation
 */
class RequestControllerNotificationTest extends WebTestCase
{
    const PHONE = '2-(999)507-4625';
    const COMPANY = 'google';
    const ROLE = 'CEO';
    const REQUEST = 'request body';
    const PO_NUMBER = 'CA245566789KL';

    /** @var ObjectManager */
    protected $em;

    /** @var  ConfigManager */
    protected $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], [], true);

        $this->loadFixtures(
            [
                'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData',
                'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
            ]
        );

        $this->client->enableProfiler();

        $this->em = $this
            ->client
            ->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityManager('Oro\Bundle\UserBundle\Entity\User');

        $this->configManager = $this->client->getContainer()->get('oro_config.manager');
    }

    public function testCreateRequestEmailNotification()
    {
        $saleRep1 = $this->getReference(LoadUserData::USER1);
        $saleRep2 = $this->getReference(LoadUserData::USER2);
        $accountUser = $this->getReference(LoadUserData::ACCOUNT1_USER1);
        $accountUser->addSalesRepresentative($saleRep1);
        $accountUser->addSalesRepresentative($saleRep2);
        $this->em->flush();
        $this->createRequest();

        $this->assertEmailSent([$saleRep1, $saleRep2], 2);
    }

    public function testCreateRequestEmailNotifySalesRepsOfAccount()
    {
        $saleRep1 = $this->getReference(LoadUserData::USER1);
        $saleRep2 = $this->getReference(LoadUserData::USER2);
        $account = $this->getReference(LoadUserData::ACCOUNT1);
        $account->addSalesRepresentative($saleRep1);
        $account->addSalesRepresentative($saleRep2);
        $this->em->flush();
        $this->createRequest();
        $this->assertEmailSent([$saleRep1, $saleRep2], 2);
    }

    public function testCreateRequestShouldNotNotifyAccountSalesReps()
    {
        $saleRep1 = $this->getReference(LoadUserData::USER1);
        $account = $this->getReference(LoadUserData::ACCOUNT1);
        $account->addSalesRepresentative($saleRep1);

        $saleRep2 = $this->getReference(LoadUserData::USER2);
        $accountUser = $this->getReference(LoadUserData::ACCOUNT1_USER1);
        $accountUser->addSalesRepresentative($saleRep2);
        $this->em->flush();

        $this->configManager->set('oro_b2b_rfp.notify_assigned_sales_reps_of_the_account', 'noSaleReps');
        $this->configManager->flush();

        $this->createRequest();
        $this->assertEmailSent([$saleRep2], 1);
    }

    public function testCreateRequestShouldNotifyAccountOwner()
    {
        $owner = $this->getReference(LoadUserData::USER1);
        $account = $this->getReference(LoadUserData::ACCOUNT1);
        $account->setOwner($owner);
        $accountUser = $this->getReference(LoadUserData::ACCOUNT1_USER1);
        $accountUser->setOwner(null);
        $this->em->flush();

        $this->createRequest();
        // should notify owner
        $this->assertEmailSent([$owner], 1);
    }

    public function testCreateRequestShouldNotNotifyAccountOwner()
    {
        $owner = $this->getReference(LoadUserData::USER1);
        $saleRepresentative = $this->getReference(LoadUserData::USER2);
        $account = $this->getReference(LoadUserData::ACCOUNT1);
        $account->setOwner($owner);
        $account->addSalesRepresentative($saleRepresentative);
        $accountUser = $this->getReference(LoadUserData::ACCOUNT1_USER1);
        $accountUser->setOwner(null);
        $this->em->flush();

        $this->configManager->set('oro_b2b_rfp.notify_owner_of_account', 'noSaleReps');
        $this->configManager->flush();

        $this->createRequest();
        // should notify only sale representative, not owner
        $this->assertEmailSent([$saleRepresentative], 1);
    }

    public function testCreateRequestShouldNotifyAccountUserOwner()
    {
        $owner = $this->getReference(LoadUserData::USER1);
        $accountUser = $this->getReference(LoadUserData::ACCOUNT1);
        $accountUser->setOwner($owner);
        $account = $this->getReference(LoadUserData::ACCOUNT1);
        $account->setOwner(null, false);
        $this->em->flush();

        $this->createRequest();
        $owner = $this->getReference(LoadUserData::USER1);
        // should notify owner
        $this->assertEmailSent([$owner], 1);
    }

    public function testCreateRequestShouldNotNotifyAccountUserOwner()
    {
        $owner = $this->getReference(LoadUserData::USER1);
        $saleRep = $this->getReference(LoadUserData::USER2);
        $accountUser = $this->getReference(LoadUserData::ACCOUNT1_USER1);
        $accountUser->setOwner($owner);
        $accountUser->addSalesRepresentative($saleRep);
        $this->em->flush();

        $this->configManager->set('oro_b2b_rfp.notify_owner_of_account_user_record', 'noSaleReps');
        $this->configManager->flush();

        $this->createRequest();
        $this->assertEmailSent([$saleRep], 1);
    }

    protected function assertEmailSent(array $usersToSendTo, $numberOfMessagesExpected)
    {
        $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');
        $messages = $mailCollector->getMessages();
        $i = 0;
        foreach ($usersToSendTo as $userToSendTo) {
            $toEmails = array_keys($messages[$i]->getTo());
            $this->assertEquals($userToSendTo->getEmail(), reset($toEmails));
            $i++;
        }
        $this->assertCount($numberOfMessagesExpected, $messages);
    }

    protected function createRequest()
    {
        $authParams = static::generateBasicAuthHeader(LoadUserData::ACCOUNT1_USER1, LoadUserData::ACCOUNT1_USER1);
        $this->initClient([], $authParams);

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_frontend_request_create'));
        $form = $crawler->selectButton('Submit Request For Quote')->form();

        $crfToken = $this->getContainer()->get('security.csrf.token_manager')->getToken('orob2b_rfp_frontend_request');

        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference('product_price.1');

        $parameters = [
            'input_action' => 'save_and_stay',
            'orob2b_rfp_frontend_request' => $this->getFormData()
        ];
        $parameters['orob2b_rfp_frontend_request']['_token'] = $crfToken;
        $parameters['orob2b_rfp_frontend_request']['requestProducts'] = [
            [
                'product' => $productPrice->getProduct()->getId(),
                'requestProductItems' => [
                    [
                        'quantity' => $productPrice->getQuantity(),
                        'productUnit' => $productPrice->getUnit()->getCode(),
                        'price' => [
                            'value' => $productPrice->getPrice()->getValue(),
                            'currency' => $productPrice->getPrice()->getCurrency()
                        ]
                    ]
                ]
            ]
        ];

        $this->client->request($form->getMethod(), $form->getUri(), $parameters);
    }

    protected function getFormData()
    {
        return [
            'firstName' => LoadRequestData::FIRST_NAME,
            'lastName' => LoadRequestData::LAST_NAME,
            'email' => LoadRequestData::EMAIL,
            'phone' => static::PHONE,
            'role' => static::ROLE,
            'company' => static::COMPANY,
            'note' => static::REQUEST,
            'poNumber' => static::PO_NUMBER,
        ];
    }
}
