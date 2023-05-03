<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Layout\DataProvider\FrontendConsentProvider;
use Oro\Bundle\ConsentBundle\Model\ConsentData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadConsentConfigData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadConsentsData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadScopeData;
use Oro\Bundle\ConsentBundle\Tests\Functional\Entity\ConsentFeatureTrait;
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
    use ConsentFeatureTrait;

    private FrontendConsentProvider $provider;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->loadFixtures([
            LoadConsentConfigData::class
        ]);

        $this->initFrontendRequest();
        $this->enableConsentFeature();

        $this->provider = $this->getContainer()->get('oro_consent.layout.data_provider.consent');
    }

    /**
     * @dataProvider getAllConsentDataProvider
     */
    public function testGetAllConsentData(string $customerUserReference, array $expectedConsentReferences): void
    {
        /** @var CustomerUser $customerUserData */
        $customerUserData = $this->getReference($customerUserReference);

        $this->getContainer()->get('security.token_storage')
            ->setToken(new UsernamePasswordToken($customerUserData, LoadCustomerUserData::PASSWORD, 'key'));

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

        $this->getContainer()->get('security.token_storage')
            ->setToken(new UsernamePasswordToken($customerUserData, LoadCustomerUserData::PASSWORD, 'key'));

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

        $this->getContainer()->get('security.token_storage')
            ->setToken(new UsernamePasswordToken($customerUserData, LoadCustomerUserData::PASSWORD, 'key'));

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

        $this->getContainer()->get('security.token_storage')
            ->setToken(new UsernamePasswordToken($customerUserData, LoadCustomerUserData::PASSWORD, 'key'));

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

    /**
     * @param ConsentData[] $consentData
     * @param array $expectedConsentReferences
     */
    private function assertExpectedConsents(array $consentData, array $expectedConsentReferences): void
    {
        $expectedConsentIds = array_map(
            fn ($consentReference) => $this->getReference($consentReference)->getId(),
            $expectedConsentReferences
        );

        $consentIds = array_map(
            static fn (ConsentData $consent) => $consent->getId(),
            $consentData
        );

        self::assertSame($expectedConsentIds, $consentIds);
    }

    /**
     * Prepare and set request object to the request stack
     */
    private function initFrontendRequest(): void
    {
        $request = Request::create('');
        $request->attributes = new ParameterBag(
            [
                '_web_content_scope' => $this->getReference(LoadScopeData::CATALOG_1_SCOPE)
            ]
        );
        $this->getContainer()->get('request_stack')->push($request);
    }
}
