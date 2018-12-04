<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadConsentsData;
use Oro\Bundle\ConsentBundle\Tests\Functional\Entity\ConsentFeatureTrait;
use Oro\Bundle\ContactUsBundle\Entity\ContactRequest;
use Oro\Bundle\ContactUsBundle\Entity\Repository\ContactReasonRepository;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CreateContactRequestListenerTest extends WebTestCase
{
    use ConsentFeatureTrait;

    /**
     * @var EntityManager
     */
    private $consentAcceptanceManager;

    /**
     * @var ContactReasonRepository
     */
    private $contactRequestRepository;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadConsentsData::class
        ]);

        $doctrine = static::getContainer()->get('doctrine');
        $this->consentAcceptanceManager = $doctrine->getManagerForClass(ConsentAcceptance::class);
        $this->contactRequestRepository = $doctrine->getManagerForClass(ContactRequest::class)
            ->getRepository(ContactRequest::class);
        $this->enableConsentFeature();
    }

    /**
     * @dataProvider listenerProvider
     *
     * @param string $customerEmail
     * @param array $acceptances
     * @param int $expectedNotificationCount
     */
    public function testConsentAcceptancePostRemove($customerEmail, $acceptances, $expectedNotificationCount)
    {
        $customer = $this->getReference($customerEmail);
        foreach ($acceptances as $acceptance) {
            $acceptance = $this->getReference($acceptance);
            $this->assertEquals($customer, $acceptance->getCustomerUser()->getEmail());
            $this->consentAcceptanceManager->remove($acceptance);
        }

        $this->consentAcceptanceManager->flush();

        $contactRequests = $this->contactRequestRepository->findBy(['emailAddress' => $customerEmail]);

        $this->assertEquals($expectedNotificationCount, count($contactRequests));
        /** @var ContactRequest $contactRequest */
        foreach ($contactRequests as $contactRequest) {
            $this->assertNotNull($contactRequest);
            $this->assertEquals($contactRequest->getFirstName(), $customer->getFirstName());
            $this->assertEquals($contactRequest->getLastName(), $customer->getLastName());
            $this->assertEquals($contactRequest->getEmailAddress(), $customer->getEmail());
            $this->assertEquals($contactRequest->getCustomerUser(), $customer);
        }
    }

    /**
     * @return \Generator
     */
    public function listenerProvider()
    {
        yield "Contact Request won't be created (consent option notify is false)" => [
            'customer' => LoadCustomerUserData::EMAIL,
            'acceptances' => [
                LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                    LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_CMS
                )
            ],
            'expectedNotificationCount' => 0
        ];

        yield "Contact Request will be created" => [
            'customer' => LoadCustomerUserData::EMAIL,
            'acceptances' => [
                LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                    LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS
                ),
                LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                    LoadConsentsData::CONSENT_REQUIRED_NODE2_WITH_CMS
                )
            ],
            'expectedNotificationCount' => 2
        ];
    }
}
