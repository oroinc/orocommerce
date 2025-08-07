<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadConsentsData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadPageDataWithSlug;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ConsentAcceptanceRepositoryTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?int $initialWebCatalogId;
    private ConsentAcceptanceRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadConsentsData::class]);

        $configManager = self::getConfigManager();
        $this->initialWebCatalogId = $configManager->get('oro_web_catalog.web_catalog');
        $configManager->set(
            'oro_web_catalog.web_catalog',
            $this->getReference(LoadWebCatalogData::CATALOG_1_USE_IN_ROUTING)->getId()
        );
        $configManager->flush();

        $this->repository = self::getContainer()->get('doctrine')->getRepository(ConsentAcceptance::class);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_web_catalog.web_catalog', $this->initialWebCatalogId);
        $configManager->flush();
    }

    public function testGetAcceptedConsentsByCustomer(): void
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
     */
    public function testHasLandingPageAcceptedConsents(string $pageName, bool $expectedResult): void
    {
        $landingPage = $this->getReference($pageName);
        $result = $this->repository->hasLandingPageAcceptedConsents($landingPage);
        $this->assertEquals($expectedResult, $result);
    }

    public function landingPagesProvider(): array
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
     * @dataProvider hasAcceptedConsentsProvider
     */
    public function testHasConsentAcceptancesByConsent(string $consentName, bool $expectedResult): void
    {
        /** @var Consent $consent */
        $consent = $this->getReference($consentName);
        $result = $this->repository->hasConsentAcceptancesByConsent($consent);
        $this->assertEquals($expectedResult, $result);
    }

    public function hasAcceptedConsentsProvider(): array
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
