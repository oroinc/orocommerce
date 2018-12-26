<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Layout\DataProvider\FrontendConsentProvider;
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

    /** @var ConsentDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $consentDataProvider;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var FrontendConsentProvider */
    private $frontendConsentProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->consentDataProvider = $this->createMock(ConsentDataProvider::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->frontendConsentProvider = new FrontendConsentProvider($this->consentDataProvider, $this->tokenStorage);
        $this->frontendConsentProvider->setFeatureChecker($this->featureChecker);
        $this->frontendConsentProvider->addFeature('consents');
    }

    public function testGetAllConsentData()
    {
        $expectedData = ['1_3' => $this->getConsentData('first', 1, 3), '2_5' => $this->getConsentData('second', 2, 5)];

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->consentDataProvider->expects($this->once())
            ->method('getAllConsentData')
            ->willReturn($expectedData);

        $this->assertEquals($expectedData, $this->frontendConsentProvider->getAllConsentData());
    }

    public function testGetAllConsentDataFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(false);

        $this->consentDataProvider->expects($this->never())
            ->method('getAllConsentData');

        $this->assertEquals([], $this->frontendConsentProvider->getAllConsentData());
    }

    public function testGetNotAcceptedRequiredConsentData()
    {
        $expectedData = [$this->getConsentData('first', 1, 3), $this->getConsentData('second', 2, 5)];

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->consentDataProvider->expects($this->once())
            ->method('getNotAcceptedRequiredConsentData')
            ->willReturn($expectedData);

        $this->assertEquals($expectedData, $this->frontendConsentProvider->getNotAcceptedRequiredConsentData());
    }

    public function testGetNotAcceptedRequiredConsentDataWithExclusion()
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

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->consentDataProvider->expects($this->once())
            ->method('getNotAcceptedRequiredConsentData')
            ->willReturn($consentData);

        $this->assertEquals(
            [$this->getConsentData('second', 2, 5)],
            $this->frontendConsentProvider->getNotAcceptedRequiredConsentData(
                [$consentAcceptance, $consentAcceptanceWithoutPage]
            )
        );
    }

    public function testGetNotAcceptedRequiredConsentDataFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(false);

        $this->consentDataProvider->expects($this->never())
            ->method('getNotAcceptedRequiredConsentData');

        $this->assertEquals([], $this->frontendConsentProvider->getNotAcceptedRequiredConsentData());
    }

    public function testIsCustomerUserCurrentlyLoggedIn()
    {
        $customerUser = new CustomerUser();
        $customerUser->setFirstName('first name');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertTrue($this->frontendConsentProvider->isCustomerUserCurrentlyLoggedIn($customerUser));
    }

    public function testIsCustomerUserCurrentlyLoggedInNotCustomerUser()
    {
        $customerUser = new CustomerUser();
        $customerUser->setFirstName('first name');

        $currentCustomerUser = new User();

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($currentCustomerUser);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertFalse($this->frontendConsentProvider->isCustomerUserCurrentlyLoggedIn($customerUser));
    }

    public function testIsCustomerUserCurrentlyLoggedInNotMatch()
    {
        $customerUser = new CustomerUser();
        $customerUser->setFirstName('first name');

        $currentCustomerUser = new CustomerUser();
        $currentCustomerUser->setFirstName('another name');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($currentCustomerUser);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertFalse($this->frontendConsentProvider->isCustomerUserCurrentlyLoggedIn($customerUser));
    }

    /**
     * @dataProvider excludedStepsDataProvider
     *
     * @param array $notAcceptedData
     * @param bool $hideConsentsStep
     * @param array $excludedSteps
     * @param array $expected
     */
    public function testGetExcludedSteps($notAcceptedData, $hideConsentsStep, $excludedSteps, $expected)
    {
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->consentDataProvider->expects($this->any())
            ->method('getNotAcceptedRequiredConsentData')
            ->willReturn($notAcceptedData);

        $this->assertEquals(
            $expected,
            $this->frontendConsentProvider->getExcludedSteps($excludedSteps, $hideConsentsStep)
        );
    }

    public function testGetExcludedStepsFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(false);

        $this->assertEquals(
            ['another_step', 'customer_consents'],
            $this->frontendConsentProvider->getExcludedSteps(['another_step'])
        );
    }

    /**
     * @return array
     */
    public function excludedStepsDataProvider()
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

    /**
     * @param string $consentDefaultName
     * @param int $consentId
     * @param int $cmsPageId
     *
     * @return ConsentData
     */
    private function getConsentData($consentDefaultName, $consentId, $cmsPageId)
    {
        $consent = $this->getEntity(Consent::class, ['id' => $consentId, 'defaultName' => $consentDefaultName]);
        $cmsPageData = new CmsPageData();
        $cmsPageData->setId($cmsPageId);

        $consentData = new ConsentData($consent);
        $consentData->setCmsPageData($cmsPageData);

        return $consentData;
    }
}
