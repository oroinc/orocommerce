<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Condition;

use Oro\Bundle\ConsentBundle\Condition\IsConsentsAccepted;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\ConsentBundle\Provider\EnabledConsentProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class IsConsentsAcceptedTest extends \PHPUnit\Framework\TestCase
{
    /** @var IsConsentsAccepted */
    private $condition;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var EnabledConsentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $enabledConsentsProvider;

    /** @var ConsentAcceptanceProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $consentAcceptanceProvider;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var ContextAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->enabledConsentsProvider = $this->createMock(EnabledConsentProvider::class);
        $this->consentAcceptanceProvider = $this->createMock(ConsentAcceptanceProvider::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->condition = new IsConsentsAccepted(
            $this->enabledConsentsProvider,
            $this->consentAcceptanceProvider,
            $this->tokenStorage
        );
        $this->condition->setFeatureChecker($this->featureChecker);

        $this->contextAccessor = $this->createMock(ContextAccessorInterface::class);
        $this->condition->setContextAccessor($this->contextAccessor);
    }

    public function testGetName()
    {
        $this->assertEquals(IsConsentsAccepted::NAME, $this->condition->getName());
    }

    public function testInitializeArrayInOptions()
    {
        $this->assertInstanceOf(IsConsentsAccepted::class, $this->condition->initialize([]));
    }

    /**
     * @dataProvider evaluateProvider
     *
     * @param array $consents
     * @param bool $expected
     */
    public function testEvaluateCustomerUser(array $consents, $expected)
    {
        $consentAcceptances = [new ConsentAcceptance(), new ConsentAcceptance()];

        $this->featureChecker
            ->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents')
            ->willReturn(true);

        $this->enabledConsentsProvider
            ->expects($this->once())
            ->method('getUnacceptedRequiredConsents')
            ->with($consentAcceptances)
            ->willReturn($consents);

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(new CustomerUser());
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->consentAcceptanceProvider
            ->expects($this->once())
            ->method('getCustomerConsentAcceptances')
            ->willReturn($consentAcceptances);

        $this->contextAccessor
            ->expects($this->never())
            ->method('hasValue');

        $this->contextAccessor
            ->expects($this->never())
            ->method('getValue');

        $this->condition->initialize([]);
        $this->assertEquals($expected, $this->condition->evaluate([]));
    }

    /**
     * @dataProvider evaluateProvider
     *
     * @param array $consents
     * @param bool $expected
     */
    public function testEvaluateIsGuest(array $consents, $expected)
    {
        $consentAcceptances = [new ConsentAcceptance(), new ConsentAcceptance()];

        $this->featureChecker
            ->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents')
            ->willReturn(true);

        $this->enabledConsentsProvider
            ->expects($this->once())
            ->method('getUnacceptedRequiredConsents')
            ->with($consentAcceptances)
            ->willReturn($consents);

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(null);
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->consentAcceptanceProvider
            ->expects($this->never())
            ->method('getCustomerConsentAcceptances');

        $this->contextAccessor
            ->expects($this->any())
            ->method('hasValue')
            ->willReturn(true);

        $this->contextAccessor
            ->expects($this->any())
            ->method('getValue')
            ->willReturn($consentAcceptances);

        $propertyPath = new PropertyPath('acceptedConsents');
        $this->condition->initialize(['acceptedConsents' => $propertyPath]);
        $this->assertEquals($expected, $this->condition->evaluate([]));
    }

    public function testEvaluateFeatureDisabled()
    {
        $this->featureChecker
            ->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents')
            ->willReturn(false);

        $this->enabledConsentsProvider
            ->expects($this->never())
            ->method('getUnacceptedRequiredConsents');

        $this->tokenStorage
            ->expects($this->never())
            ->method('getToken');

        $this->condition->initialize([]);
        $this->assertTrue($this->condition->evaluate([]));
    }

    /**
     * @return array
     */
    public function evaluateProvider()
    {
        return [
            'has unaccepted consents' => [
                'consents' => [new Consent(), new Consent()],
                'expected' => false,
            ],
            'all consents was accepted' => [
                'consents' => [],
                'expected' => true,
            ],
        ];
    }
}
