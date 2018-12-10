<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConsentBundle\Layout\DataProvider\FrontendConsentProvider;
use Oro\Bundle\ConsentBundle\Model\ConsentData;
use Oro\Bundle\ConsentBundle\Provider\ConsentDataProvider;
use Oro\Bundle\ConsentBundle\Tests\Unit\Entity\Stub\Consent;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FrontendConsentProviderTest extends \PHPUnit\Framework\TestCase
{
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
        $expectedData = [$this->getConsentData('first'), $this->getConsentData('second')];

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

    public function testGetRequiredConsentData()
    {
        $expectedData = [$this->getConsentData('first'), $this->getConsentData('second')];

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->consentDataProvider->expects($this->once())
            ->method('getRequiredConsentData')
            ->willReturn($expectedData);

        $this->assertEquals($expectedData, $this->frontendConsentProvider->getRequiredConsentData());
    }

    public function testGetRequiredConsentDataFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(false);

        $this->consentDataProvider->expects($this->never())
            ->method('getRequiredConsentData');

        $this->assertEquals([], $this->frontendConsentProvider->getRequiredConsentData());
    }

    public function testGetAcceptedConsentData()
    {
        $expectedData = [$this->getConsentData('first'), $this->getConsentData('second')];

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->consentDataProvider->expects($this->once())
            ->method('getAcceptedConsentData')
            ->willReturn($expectedData);

        $this->assertEquals($expectedData, $this->frontendConsentProvider->getAcceptedConsentData());
    }

    public function testGetAcceptedConsentDataFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(false);

        $this->consentDataProvider->expects($this->never())
            ->method('getAcceptedConsentData');

        $this->assertEquals([], $this->frontendConsentProvider->getAcceptedConsentData());
    }

    public function testGetNotAcceptedRequiredConsentData()
    {
        $expectedData = [$this->getConsentData('first'), $this->getConsentData('second')];

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->consentDataProvider->expects($this->once())
            ->method('getNotAcceptedRequiredConsentData')
            ->willReturn($expectedData);

        $this->assertEquals($expectedData, $this->frontendConsentProvider->getNotAcceptedRequiredConsentData());
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

    public function testGetExcludedSteps()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->assertEquals([], $this->frontendConsentProvider->getExcludedSteps());
    }

    public function testGetExcludedStepsWithPredefinedSteps()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->assertEquals(['another_step'], $this->frontendConsentProvider->getExcludedSteps(['another_step']));
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

    public function testGetStepOrder()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->assertEquals(2, $this->frontendConsentProvider->getStepOrder(2));
    }

    public function testGetStepOrderFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(false);

        $this->assertEquals(1, $this->frontendConsentProvider->getStepOrder(2));
    }

    /**
     * @param string $consentDefaultName
     *
     * @return ConsentData
     */
    private function getConsentData($consentDefaultName)
    {
        $consent = new Consent();
        $consent->setDefaultName($consentDefaultName);

        return new ConsentData($consent);
    }
}
