<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Manager;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Manager\GuestCustomerConsentAcceptancesManager;
use Oro\Bundle\ConsentBundle\Storage\SessionCustomerConsentAcceptancesStorage;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadConsentsData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadPageDataWithSlug;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;

class GuestCustomerConsentAcceptancesManagerTest extends WebTestCase
{
    use EntityTrait;

    /**
     * @var GuestCustomerConsentAcceptancesManager
     */
    private $manager;

    /**
     * @var SessionCustomerConsentAcceptancesStorage| \PHPUnit\Framework\MockObject\MockObject
     */
    private $storage;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadConsentsData::class
        ]);

        $this->storage = $this->createMock(SessionCustomerConsentAcceptancesStorage::class);
        $this->manager = $this->getContainer()->get('oro_consent.manager.guest_customer_consent_acceptances_manager');
        $this->manager->setStorage($this->storage);
    }

    /**
     * @param $sessionData
     * @param $expectedAcceptancesCount
     * @dataProvider consentAcceptanceStorageProvider
     */
    public function testFlushCustomerConsentAcceptancesFromStorage($sessionData, $expectedAcceptancesCount)
    {
        $customerUser = $this->getReference(LoadCustomerUserData::LEVEL_1_EMAIL);
        $consentAcceptances = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroConsentBundle:ConsentAcceptance')
            ->getRepository('OroConsentBundle:ConsentAcceptance')
            ->findBy(['customerUser' => $customerUser]);

        $this->assertEquals(count($consentAcceptances), 0);

        $originalSessionData = array_map([$this, 'prepareSessionData'], $sessionData);
        $this->storage->expects($this->once())
            ->method('getData')
            ->willReturn($originalSessionData);

        $this->manager->flushCustomerConsentAcceptancesFromStorage($customerUser);
        $consentAcceptances = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroConsentBundle:ConsentAcceptance')
            ->getRepository('OroConsentBundle:ConsentAcceptance')
            ->findBy(['customerUser' => $customerUser]);
        $this->assertEquals(count($consentAcceptances), $expectedAcceptancesCount);
    }

    private function prepareSessionData(array $sessionData)
    {
        return $this->getEntity(
            ConsentAcceptance::class,
            [
                'consent' => $this->getReference($sessionData['consentReference']),
                'landingPage' => $this->getReference($sessionData['landingPageReference'])
            ]
        );
    }

    /**
     * @return \Generator
     */
    public function consentAcceptanceStorageProvider()
    {
        yield 'no data in session' => [
            'sessionData' => [],
            'expectedAcceptancesCount' => 0
        ];

        yield 'session has data' => [
            'sessionData' => [
                [
                    'consentReference' => LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_CMS,
                    'landingPageReference' => LoadPageDataWithSlug::PAGE_1
                ],
                [
                    'consentReference' => LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_SYSTEM,
                    'landingPageReference' => LoadPageDataWithSlug::PAGE_2
                ]
            ],
            'expectedAcceptancesCount' => 2
        ];
    }
}
