<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Layout\DataProvider\FrontendConsentProvider;
use Oro\Bundle\ConsentBundle\Layout\DTO\RequiredConsentData;
use Oro\Bundle\ConsentBundle\Model\CmsPageData;
use Oro\Bundle\ConsentBundle\Model\ConsentData;
use Oro\Bundle\ConsentBundle\Provider\ConsentDataProvider;
use Oro\Bundle\ConsentBundle\Tests\Unit\Entity\Stub\Consent;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FrontendConsentProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private ConsentDataProvider|\PHPUnit\Framework\MockObject\MockObject $consentDataProvider;

    private TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject $tokenStorage;

    private FeatureChecker|\PHPUnit\Framework\MockObject\MockObject $featureChecker;

    private FrontendConsentProvider $frontendConsentProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->consentDataProvider = $this->createMock(ConsentDataProvider::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->frontendConsentProvider = new FrontendConsentProvider($this->consentDataProvider, $this->tokenStorage);
        $this->frontendConsentProvider->setFeatureChecker($this->featureChecker);
        $this->frontendConsentProvider->addFeature('consents');
    }

    public function testGetAllConsentData(): void
    {
        $expectedData = ['1_3' => $this->getConsentData('first', 1, 3), '2_5' => $this->getConsentData('second', 2, 5)];

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->consentDataProvider->expects(self::once())
            ->method('getAllConsentData')
            ->willReturn($expectedData);

        self::assertEquals($expectedData, $this->frontendConsentProvider->getAllConsentData());
    }

    public function testGetAllConsentDataFeatureDisabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(false);

        $this->consentDataProvider->expects(self::never())
            ->method('getAllConsentData');

        self::assertEquals([], $this->frontendConsentProvider->getAllConsentData());
    }

    public function testGetNotAcceptedRequiredConsentData(): void
    {
        $expectedData = [$this->getConsentData('first', 1, 3), $this->getConsentData('second', 2, 5)];

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->consentDataProvider->expects(self::once())
            ->method('getNotAcceptedRequiredConsentData')
            ->willReturn($expectedData);

        self::assertEquals($expectedData, $this->frontendConsentProvider->getNotAcceptedRequiredConsentData());
    }

    public function testGetNotAcceptedRequiredConsentDataWithExclusion(): void
    {
        $consentAcceptance = new ConsentAcceptance();
        $consentAcceptance->setConsent($this->getEntity(Consent::class, ['id' => 1]));
        $consentAcceptance->setLandingPage($this->getEntity(Page::class, ['id' => 3]));

        $consentAcceptanceWithoutPage = new ConsentAcceptance();
        $consentAcceptanceWithoutPage->setConsent($this->getEntity(Consent::class, ['id' => 3]));

        $consentData = [
            '1_3' => $this->getConsentData('first', 1, 3),
            '2_5' =>$this->getConsentData('second', 2, 5),
            '3_' =>$this->getConsentData('second', 3, null)
        ];

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->consentDataProvider->expects(self::once())
            ->method('getNotAcceptedRequiredConsentData')
            ->willReturn($consentData);

        self::assertEquals(
            [$this->getConsentData('second', 2, 5)],
            $this->frontendConsentProvider->getNotAcceptedRequiredConsentData(
                [$consentAcceptance, $consentAcceptanceWithoutPage]
            )
        );
    }

    public function testGetNotAcceptedRequiredConsentDataFeatureDisabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(false);

        $this->consentDataProvider->expects(self::never())
            ->method('getNotAcceptedRequiredConsentData');

        self::assertEquals([], $this->frontendConsentProvider->getNotAcceptedRequiredConsentData());
    }

    public function testIsCustomerUserCurrentlyLoggedIn(): void
    {
        $customerUser = new CustomerUser();
        $customerUser->setFirstName('first name');

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertTrue($this->frontendConsentProvider->isCustomerUserCurrentlyLoggedIn($customerUser));
    }

    public function testIsCustomerUserCurrentlyLoggedInNotCustomerUser(): void
    {
        $customerUser = new CustomerUser();
        $customerUser->setFirstName('first name');

        $currentCustomerUser = new User();

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($currentCustomerUser);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertFalse($this->frontendConsentProvider->isCustomerUserCurrentlyLoggedIn($customerUser));
    }

    public function testIsCustomerUserCurrentlyLoggedInNotMatch(): void
    {
        $customerUser = new CustomerUser();
        $customerUser->setFirstName('first name');

        $currentCustomerUser = new CustomerUser();
        $currentCustomerUser->setFirstName('another name');

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($currentCustomerUser);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertFalse($this->frontendConsentProvider->isCustomerUserCurrentlyLoggedIn($customerUser));
    }

    /**
     * @dataProvider excludedStepsDataProvider
     */
    public function testGetExcludedSteps(
        array $notAcceptedData,
        bool $hideConsentsStep,
        array $excludedSteps,
        array $expected
    ): void {
        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->consentDataProvider->expects(self::any())
            ->method('getNotAcceptedRequiredConsentData')
            ->willReturn($notAcceptedData);

        self::assertEquals(
            $expected,
            $this->frontendConsentProvider->getExcludedSteps($excludedSteps, $hideConsentsStep)
        );
    }

    public function testGetExcludedStepsFeatureDisabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(false);

        self::assertEquals(
            ['another_step', 'customer_consents'],
            $this->frontendConsentProvider->getExcludedSteps(['another_step'])
        );
    }

    public function excludedStepsDataProvider(): array
    {
        return [
            'with predefined steps and hide consents step' => [
                'notAcceptedData' => [],
                'hideConsentsStep' => true,
                'excludedSteps' => ['another_step'],
                'expected' => ['another_step', 'customer_consents'],
            ],
            'with predefined steps and show consents step' => [
                'notAcceptedData' => [],
                'hideConsentsStep' => false,
                'excludedSteps' => ['another_step'],
                'expected' => ['another_step'],
            ],
            'with predefined steps and hide consents step and with not accepted data' => [
                'notAcceptedData' => [new ConsentData(new Consent())],
                'hideConsentsStep' => true,
                'excludedSteps' => ['another_step'],
                'expected' => ['another_step'],
            ],
            'without predefined steps and hide consents step' => [
                'notAcceptedData' => [],
                'hideConsentsStep' => true,
                'excludedSteps' => [],
                'expected' => ['customer_consents'],
            ]
        ];
    }

    public function testGetRequiredConsentDataFeatureDisabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(false);

        $this->consentDataProvider->expects(self::never())
            ->method('getRequiredConsentData');

        self::assertEquals(new RequiredConsentData(), $this->frontendConsentProvider->getAcceptedRequiredConsentData());
    }

    /**
     * @dataProvider getRequiredConsentDataProvider
     */
    public function testGetRequiredConsentData(
        array $requiredConsentData,
        RequiredConsentData $expectedResult,
        array $consentAcceptances = []
    ): void {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->consentDataProvider->expects(self::once())
            ->method('getRequiredConsentData')
            ->willReturn($requiredConsentData);

        self::assertEquals(
            $expectedResult,
            $this->frontendConsentProvider->getAcceptedRequiredConsentData($consentAcceptances)
        );
    }

    public function getRequiredConsentDataProvider(): array
    {
        $acceptedRequiredConsent = $this->getConsentData('foo', 1, 3, true);
        $notAcceptedRequiredConsentBar = $this->getConsentData('bar', 2, 5);
        $notAcceptedRequiredConsentBaz = $this->getConsentData('baz', 2, 5);

        $requiredConsentData = [
            $acceptedRequiredConsent,
            $notAcceptedRequiredConsentBar,
            $notAcceptedRequiredConsentBaz
        ];

        $consentAcceptanceBaz = new ConsentAcceptance();
        $consentAcceptanceBaz->setConsent($this->getEntity(Consent::class, ['id' => 2]));
        $consentAcceptanceBaz->setLandingPage($this->getEntity(Page::class, ['id' => 5]));

        return [
            'not required and acceptance' => [
                'requiredConsentData' => [],
                'expectedResult' => new RequiredConsentData(),
                'consentAcceptances' => [$consentAcceptanceBaz],
            ],
            'required, no acceptance' => [
                'requiredConsentData' => $requiredConsentData,
                'expectedResult' => new RequiredConsentData(
                    [$acceptedRequiredConsent],
                    3
                ),
                'consentAcceptances' => [],
            ],
            'required and acceptance' => [
                'requiredConsentData' => $requiredConsentData,
                'expectedResult' => new RequiredConsentData(
                    [$acceptedRequiredConsent, $notAcceptedRequiredConsentBaz],
                    3
                ),
                'consentAcceptances' => [$consentAcceptanceBaz],
            ],
        ];
    }

    public function testGetConsentDataFeatureDisabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(false);

        $this->consentDataProvider->expects(self::never())
            ->method('getNotAcceptedRequiredConsentData');

        self::assertEquals([], $this->frontendConsentProvider->getConsentData());
    }

    /**
     * @dataProvider getConsentDataProvider
     */
    public function testGetConsentData(
        array $notAcceptedRequiredData,
        array $expectedData,
        array $consentAcceptances = []
    ): void {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->consentDataProvider->expects(self::once())
            ->method('getNotAcceptedRequiredConsentData')
            ->willReturn($notAcceptedRequiredData);

        self::assertEquals($expectedData, $this->frontendConsentProvider->getConsentData($consentAcceptances));
    }

    public function getConsentDataProvider(): array
    {
        $notAcceptedRequiredData = [$this->getConsentData('first', 1, 3), $this->getConsentData('second', 2, 5)];
        $expectedData = [$this->getConsentData('first', 1, 3), $this->getConsentData('second', 2, 5, true)];

        $consentAcceptanceBaz = new ConsentAcceptance();
        $consentAcceptanceBaz->setConsent($this->getEntity(Consent::class, ['id' => 2]));
        $consentAcceptanceBaz->setLandingPage($this->getEntity(Page::class, ['id' => 5]));

        return [
            'all accepted and acceptance' => [
                'notAcceptedRequiredData' => [],
                'expectedData' => [],
                'consentAcceptances' => [$consentAcceptanceBaz],
            ],
            'not accepted, no acceptance' => [
                'notAcceptedRequiredData' => $notAcceptedRequiredData,
                'expectedData' => $notAcceptedRequiredData,
                'consentAcceptances' => [],
            ],
            'not accepted and acceptance' => [
                'notAcceptedRequiredData' => $notAcceptedRequiredData,
                'expectedData' => $expectedData,
                'consentAcceptances' => [$consentAcceptanceBaz],
            ],
        ];
    }

    private function getConsentData(
        string $consentDefaultName,
        int $consentId,
        ?int $cmsPageId,
        bool $isAccepted = false
    ): ConsentData {
        $consent = $this->getEntity(Consent::class, ['id' => $consentId, 'defaultName' => $consentDefaultName]);
        $cmsPageData = new CmsPageData();
        $cmsPageData->setId($cmsPageId);


        $consentData = new ConsentData($consent);
        $consentData->setCmsPageData($cmsPageData);
        $consentData->setAccepted($isAccepted);

        return $consentData;
    }
}
