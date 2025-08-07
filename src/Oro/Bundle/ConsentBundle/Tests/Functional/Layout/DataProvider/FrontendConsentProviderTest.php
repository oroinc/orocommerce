<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Layout\DataProvider\FrontendConsentProvider;
use Oro\Bundle\ConsentBundle\Model\ConsentData;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigConverter;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadConsentsData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadScopeData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @covers \Oro\Bundle\ConsentBundle\Layout\DataProvider\FrontendConsentProvider
 * @covers \Oro\Bundle\ConsentBundle\Provider\CustomerUserConsentProvider
 */
class FrontendConsentProviderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?int $initialWebCatalogId;
    private ?array $initialEnabledConsents;
    private FrontendConsentProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadConsentsData::class]);

        $configManager = self::getConfigManager();
        $this->initialWebCatalogId = $configManager->get('oro_web_catalog.web_catalog');
        $this->initialEnabledConsents = $configManager->get('oro_consent.enabled_consents');
        $configManager->set(
            'oro_web_catalog.web_catalog',
            $this->getReference(LoadWebCatalogData::CATALOG_1_USE_IN_ROUTING)->getId()
        );
        $configManager->set('oro_consent.enabled_consents', $this->getEnabledConsents());
        $configManager->set('oro_consent.consent_feature_enabled', true);
        $configManager->flush();

        $this->initFrontendRequest();

        $this->provider = self::getContainer()->get('oro_consent.layout.data_provider.consent');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_web_catalog.web_catalog', $this->initialWebCatalogId);
        $configManager->set('oro_consent.enabled_consents', $this->initialEnabledConsents);
        $configManager->set('oro_consent.consent_feature_enabled', false);
        $configManager->flush();
    }

    private function getEnabledConsents(): array
    {
        $consentConfig = array_map(
            function ($consentReference) {
                return new ConsentConfig($this->getReference($consentReference));
            },
            [
                LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_CMS,
                LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_SYSTEM,
                LoadConsentsData::CONSENT_OPTIONAL_NODE2_WITH_CMS,
                LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS,
                LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_SYSTEM,
                LoadConsentsData::CONSENT_REQUIRED_NODE2_WITH_CMS,
                LoadConsentsData::CONSENT_OPTIONAL_WITHOUT_NODE,
                LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE,
            ]
        );

        /** @var ConsentConfigConverter $consentConfigConverter */
        $consentConfigConverter = self::getContainer()->get('oro_consent.system_config.consent_config_converter');

        return $consentConfigConverter->convertBeforeSave($consentConfig);
    }

    /**
     * Prepare and set request object to the request stack
     */
    private function initFrontendRequest(): void
    {
        $request = Request::create('');
        $request->attributes = new ParameterBag([
            '_web_content_scope' => $this->getReference(LoadScopeData::CATALOG_1_SCOPE)
        ]);
        self::getContainer()->get('request_stack')->push($request);
    }

    private function assertExpectedConsents(array $consentData, array $expectedConsentReferences): void
    {
        $expectedConsentIds = array_map(
            fn ($consentReference) => $this->getReference($consentReference)->getId(),
            $expectedConsentReferences
        );
        $consentIds = array_map(
            function (ConsentData $consent) {
                return $consent->getId();
            },
            $consentData
        );
        self::assertSame($expectedConsentIds, $consentIds);
    }

    /**
     * @dataProvider getAllConsentDataProvider
     */
    public function testGetAllConsentData(string $customerUserReference, array $expectedConsentReferences): void
    {
        /** @var CustomerUser $customerUserData */
        $customerUserData = $this->getReference($customerUserReference);

        self::getContainer()->get('security.token_storage')
            ->setToken(new UsernamePasswordToken($customerUserData, 'key'));

        $consentData = $this->provider->getAllConsentData();
        $this->assertExpectedConsents($consentData, $expectedConsentReferences);
    }

    public function getAllConsentDataProvider(): array
    {
        return [
            'Customer with consent acceptances' => [
                'customerUserReference' => LoadCustomerUserData::EMAIL,
                'expectedConsentReferences' => [
                    LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_CMS,
                    LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_SYSTEM,
                    LoadConsentsData::CONSENT_OPTIONAL_NODE2_WITH_CMS,
                    LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS,
                    LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_SYSTEM,
                    LoadConsentsData::CONSENT_REQUIRED_NODE2_WITH_CMS,
                    LoadConsentsData::CONSENT_OPTIONAL_WITHOUT_NODE,
                    LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE
                ]
            ],
            'Customer without consent acceptances' => [
                'customerUserReference' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'expectedConsentReferences' => [
                    LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_CMS,
                    LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS,
                    LoadConsentsData::CONSENT_OPTIONAL_WITHOUT_NODE,
                    LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE,
                ]
            ],
        ];
    }

    /**
     * @dataProvider getNotAcceptedRequiredConsentDataProvider
     */
    public function testGetNotAcceptedRequiredConsentData(
        string $customerUserReference,
        array $expectedConsentReferences
    ): void {
        /** @var CustomerUser $customerUserData */
        $customerUserData = $this->getReference($customerUserReference);

        self::getContainer()->get('security.token_storage')
            ->setToken(new UsernamePasswordToken($customerUserData, 'key'));

        $consentData = $this->provider->getNotAcceptedRequiredConsentData();
        $this->assertExpectedConsents($consentData, $expectedConsentReferences);
    }

    public function getNotAcceptedRequiredConsentDataProvider(): array
    {
        return [
            'Customer with consent acceptances' => [
                'customerUserReference' => LoadCustomerUserData::EMAIL,
                'expectedConsentReferences' => []
            ],
            'Customer without consent acceptances' => [
                'customerUserReference' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'expectedConsentReferences' => [
                    LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS,
                    LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE
                ]
            ],
        ];
    }

    /**
     * @dataProvider getAcceptedRequiredConsentDataProvider
     */
    public function testGetAcceptedRequiredConsentData(
        string $customerUserReference,
        array $expectedConsentReferences,
        int $expectedRequiredConsentsNumber
    ): void {
        /** @var CustomerUser $customerUserData */
        $customerUserData = $this->getReference($customerUserReference);

        self::getContainer()->get('security.token_storage')
            ->setToken(new UsernamePasswordToken($customerUserData, 'key'));

        $consentAcceptance = new ConsentAcceptance();
        $consentAcceptance->setConsent($this->getReference(LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE));
        $consentAcceptance->setCustomerUser($customerUserData);

        $requiredConsentData = $this->provider->getAcceptedRequiredConsentData([$consentAcceptance]);

        self::assertEquals($expectedRequiredConsentsNumber, $requiredConsentData->getRequiredConsentsNumber());
        $this->assertExpectedConsents(
            $requiredConsentData->getAcceptedRequiredConsentData(),
            $expectedConsentReferences
        );
    }

    public function getAcceptedRequiredConsentDataProvider(): array
    {
        return [
            'Customer without consent acceptances' => [
                'customerUserReference' => LoadCustomerUserData::EMAIL,
                'expectedConsentReferences' => [
                    LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS,
                    LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_SYSTEM,
                    LoadConsentsData::CONSENT_REQUIRED_NODE2_WITH_CMS,
                    LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE,
                ],
                'expectedRequiredConsentsNumber' => 4,
            ],
            'Customer with consent acceptances' => [
                'customerUserReference' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'expectedConsentReferences' => [
                    LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE,
                ],
                'expectedRequiredConsentsNumber' => 2,
            ],
        ];
    }

    /**
     * @dataProvider getConsentDataProvider
     */
    public function testGetConsentData(
        string $customerUserReference,
        array $expectedConsentReferences
    ): void {
        /** @var CustomerUser $customerUserData */
        $customerUserData = $this->getReference($customerUserReference);

        self::getContainer()->get('security.token_storage')
            ->setToken(new UsernamePasswordToken($customerUserData, 'key'));

        $consentAcceptance = new ConsentAcceptance();
        $consentAcceptance->setConsent($this->getReference(LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE));
        $consentAcceptance->setCustomerUser($customerUserData);

        $consentData = $this->provider->getConsentData([$consentAcceptance]);
        $this->assertExpectedConsents($consentData, $expectedConsentReferences);
    }

    public function getConsentDataProvider(): array
    {
        return [
            'Customer with consent acceptances' => [
                'customerUserReference' => LoadCustomerUserData::EMAIL,
                'expectedConsentReferences' => []
            ],
            'Customer without consent acceptances' => [
                'customerUserReference' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'expectedConsentReferences' => [
                    LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS,
                    LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE
                ]
            ],
        ];
    }
}
