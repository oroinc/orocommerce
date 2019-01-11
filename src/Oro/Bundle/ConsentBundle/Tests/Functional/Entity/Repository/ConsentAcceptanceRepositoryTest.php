<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadConsentsData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadPageDataWithSlug;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ConsentAcceptanceRepositoryTest extends WebTestCase
{
    /**
     * @var ConsentAcceptanceRepository
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadConsentsData::class,
        ]);

        $this->repository = $this
            ->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(ConsentAcceptance::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->repository);
    }

    public function testGetAcceptedConsentsByCustomer()
    {
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);
        $expectedConsentAcceptance =
            [
                $this->getReference(
                    LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                        LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_CMS
                    )
                ),
                $this->getReference(
                    LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                        LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_SYSTEM
                    )
                ),
                $this->getReference(
                    LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                        LoadConsentsData::CONSENT_OPTIONAL_NODE2_WITH_CMS
                    )
                ),
                $this->getReference(
                    LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                        LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS
                    )
                ),
                $this->getReference(
                    LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                        LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_SYSTEM
                    )
                ),
                $this->getReference(
                    LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                        LoadConsentsData::CONSENT_REQUIRED_NODE2_WITH_CMS
                    )
                ),
                $this->getReference(
                    LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                        LoadConsentsData::CONSENT_OPTIONAL_WITHOUT_NODE
                    )
                ),
                $this->getReference(
                    LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                        LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE
                    )
                ),
            ];
        $result = $this->repository->getAcceptedConsentsByCustomer($customerUser);
        $this->assertEquals($expectedConsentAcceptance, $result);
    }

    /**
     * @dataProvider landingPagesProvider
     *
     * @param string $pageName
     * @param bool   $expectedResult
     */
    public function testHasLandingPageAcceptedConsents($pageName, $expectedResult)
    {
        $landingPage = $this->getReference($pageName);
        $result = $this->repository->hasLandingPageAcceptedConsents($landingPage);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function landingPagesProvider()
    {
        return [
            'landing page has consents' => [
                'name' => LoadPageDataWithSlug::PAGE_1,
                'expected' => true
            ],
            'landing page has not consents' => [
                'name' => LoadPageDataWithSlug::PAGE_2,
                'expected' => false
            ]
        ];
    }

    /**
     * @param string $consentName
     * @param bool $expectedResult
     * @dataProvider hasAcceptedConsentsProvider
     */
    public function testHasConsentAcceptancesByConsent($consentName, $expectedResult)
    {
        /** @var Consent $consent */
        $consent = $this->getReference($consentName);
        $result = $this->repository->hasConsentAcceptancesByConsent($consent);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function hasAcceptedConsentsProvider()
    {
        return [
            'consent has acceptances' => [
                'name' => LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_CMS,
                'expected' => true,
            ],
            'consent has no acceptances'  => [
                'name' => LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_CMS_WITHOUT_ACCEPTANCES,
                'expected' => false,
            ],
        ];
    }
}
