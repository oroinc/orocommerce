<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller\Frontend;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\ProductPriceReference;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolationPerTest
 */
class RequestControllerNotificationTest extends WebTestCase
{
    use ProductPriceReference;
    use ConfigManagerAwareTestTrait;

    const PHONE = '2-(999)507-4625';
    const COMPANY = 'google';
    const ROLE = 'CEO';
    const REQUEST = 'request body';
    const PO_NUMBER = 'CA245566789KL';

    /** @var ObjectManager */
    protected $em;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @var Website
     */
    protected $website;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                LoadRequestData::class,
                LoadProductPrices::class,
            ]
        );

        $this->client->enableProfiler();

        $this->em = $this->client->getContainer()
            ->get('doctrine')
            ->getManagerForClass(User::class);
        $this->website = $this->getContainer()->get('oro_website.manager')->getDefaultWebsite();

        $this->configManager = self::getConfigManager('global');
    }

    protected function tearDown(): void
    {
        $this->getContainer()->get('swiftmailer.plugin.messagelogger')->clear();
    }

    public function testNotifyOnlyUniqueUsers()
    {
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadUserData::ACCOUNT1_USER1);
        $saleRep = $customerUser->getOwner();
        $customerUser->addSalesRepresentative($saleRep);
        $this->em->flush();

        $recipientEmails = [$saleRep->getEmail()];
        $this->createRequest();
        $this->assertEmailSent($recipientEmails, 1);
    }

    public function testCreateRequestEmailNotification()
    {
        /** @var User $saleRep1 */
        $saleRep1 = $this->getReference(LoadUserData::USER1);
        /** @var User $saleRep2 */
        $saleRep2 = $this->getReference(LoadUserData::USER2);
        $customerUser = $this->getReference(LoadUserData::ACCOUNT1_USER1);
        $customerUser->addSalesRepresentative($saleRep1);
        $customerUser->addSalesRepresentative($saleRep2);
        $this->em->flush();

        $recipientEmails = [$saleRep1->getEmail(), $saleRep2->getEmail()];
        $this->createRequest();
        $this->assertEmailSent($recipientEmails, 3);
    }

    public function testCreateRequestEmailNotifySalesRepsOfCustomer()
    {
        /** @var User $saleRep1 */
        $saleRep1 = $this->getReference(LoadUserData::USER1);
        /** @var User $saleRep2 */
        $saleRep2 = $this->getReference(LoadUserData::USER2);
        $customer = $this->getReference(LoadUserData::ACCOUNT1);
        $customer->addSalesRepresentative($saleRep1);
        $customer->addSalesRepresentative($saleRep2);
        $this->em->flush();

        $recipientEmails = [$saleRep1->getEmail(), $saleRep2->getEmail()];
        $this->createRequest();
        $this->assertEmailSent($recipientEmails, 3);
    }

    public function testCreateRequestShouldNotNotifyCustomerSalesReps()
    {
        /** @var User $saleRep1 */
        $saleRep1 = $this->getReference(LoadUserData::USER1);
        $customer = $this->getReference(LoadUserData::ACCOUNT1);
        $customer->addSalesRepresentative($saleRep1);

        /** @var User $saleRep2 */
        $saleRep2 = $this->getReference(LoadUserData::USER2);
        $customerUser = $this->getReference(LoadUserData::ACCOUNT1_USER1);
        $customerUser->addSalesRepresentative($saleRep2);
        $this->em->flush();

        $this->configManager->set('oro_rfp.notify_assigned_sales_reps_of_the_customer', 'noSaleReps');
        $this->configManager->flush();

        $recipientEmails = [$saleRep2->getEmail()];
        $this->createRequest();
        $this->assertEmailSent($recipientEmails, 2);
    }

    public function testCreateRequestShouldNotifyCustomerOwner()
    {
        /** @var User $owner */
        $owner = $this->getReference(LoadUserData::USER1);
        $customer = $this->getReference(LoadUserData::ACCOUNT1);
        $customer->setOwner($owner);
        $this->em->flush();

        $recipientEmails = [$owner->getEmail()];
        $this->createRequest();
        // should notify owner
        $this->assertEmailSent($recipientEmails, 1);
    }

    public function testCreateRequestShouldNotNotifyCustomerOwner()
    {
        /** @var User $saleRep */
        $saleRep = $this->getReference(LoadUserData::USER2);
        $customer = $this->getReference(LoadUserData::ACCOUNT1);
        $customer->addSalesRepresentative($saleRep);
        $customerUser = $this->getReference(LoadUserData::ACCOUNT1_USER1);
        $customerUser->addSalesRepresentative($saleRep);
        $this->em->flush();

        $this->configManager->set('oro_rfp.notify_owner_of_customer', 'noSaleReps');
        $this->configManager->flush();

        $recipientEmails = [$saleRep->getEmail()];
        $this->createRequest();
        // should notify only sale representative, not owner
        $this->assertEmailSent($recipientEmails, 2);
    }

    public function testCreateRequestShouldNotifyCustomerUserOwner()
    {
        /** @var User $owner */
        $owner = $this->getReference(LoadUserData::USER1);
        $customerUser = $this->getReference(LoadUserData::ACCOUNT1);
        $customerUser->setOwner($owner);
        $this->em->flush();

        $recipientEmails = [$owner->getEmail()];
        $this->createRequest();
        // should notify owner
        $this->assertEmailSent($recipientEmails, 1);
    }

    public function testCreateRequestShouldNotNotifyCustomerUserOwner()
    {
        /** @var User $owner */
        $owner = $this->getReference(LoadUserData::USER1);
        /** @var User $saleRep */
        $saleRep = $this->getReference(LoadUserData::USER2);
        $customerUser = $this->getReference(LoadUserData::ACCOUNT1_USER1);
        $customerUser->setOwner($owner);
        $customerUser->addSalesRepresentative($saleRep);
        $this->em->flush();

        $this->configManager->set('oro_rfp.notify_owner_of_customer_user_record', 'noSaleReps');
        $this->configManager->flush();

        $recipientEmails = [$saleRep->getEmail()];
        $this->createRequest();
        $this->assertEmailSent($recipientEmails, 2);
    }

    /**
     * @param string[] $recipientEmails
     * @param int $numberOfMessagesExpected
     */
    protected function assertEmailSent(array $recipientEmails, $numberOfMessagesExpected)
    {
        /** @var \Swift_Plugins_MessageLogger $emailLogging */
        $emailLogger = $this->getContainer()->get('swiftmailer.plugin.messagelogger');
        $emailMessages = $emailLogger->getMessages();
        $actualSentToEmails = [];
        foreach ($emailMessages as $message) {
            $actualSentToEmails = array_merge($actualSentToEmails, array_keys($message->getTo()));
        }

        foreach ($recipientEmails as $recipientEmail) {
            $this->assertTrue(in_array($recipientEmail, $actualSentToEmails));
        }
        $this->assertCount($numberOfMessagesExpected, $emailMessages);
    }

    protected function createRequest()
    {
        $authParams = static::generateBasicAuthHeader(LoadUserData::ACCOUNT1_USER1, LoadUserData::ACCOUNT1_USER1);
        $this->initClient([], $authParams);

        $crawler = $this->client->request('GET', $this->getUrl('oro_rfp_frontend_request_create'));
        $form = $crawler->selectButton('Submit Request')->form();

        $crfToken = $this->getContainer()->get('security.csrf.token_manager')
            ->getToken('oro_rfp_frontend_request')
            ->getValue();

        /** @var ProductPrice $productPrice */
        $productPrice = $this->getPriceByReference('product_price.1');

        $parameters = [
            'input_action' => 'save_and_stay',
            'oro_rfp_frontend_request' => $this->getFormData()
        ];
        $parameters['oro_rfp_frontend_request']['_token'] = $crfToken;
        $parameters['oro_rfp_frontend_request']['requestProducts'] = [
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

    /**
     * @return array
     */
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
