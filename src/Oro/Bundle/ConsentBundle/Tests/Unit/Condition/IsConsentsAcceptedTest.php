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
    private IsConsentsAccepted $condition;

    private FeatureChecker|\PHPUnit\Framework\MockObject\MockObject $featureChecker;

    private EnabledConsentProvider|\PHPUnit\Framework\MockObject\MockObject $enabledConsentsProvider;

    private ConsentAcceptanceProvider|\PHPUnit\Framework\MockObject\MockObject $consentAcceptanceProvider;

    private TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject $tokenStorage;

    private ContextAccessorInterface|\PHPUnit\Framework\MockObject\MockObject $contextAccessor;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
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

    public function testGetName(): void
    {
        self::assertEquals(IsConsentsAccepted::NAME, $this->condition->getName());
    }

    public function testInitializeArrayInOptions(): void
    {
        self::assertInstanceOf(IsConsentsAccepted::class, $this->condition->initialize([]));
    }

    /**
     * @dataProvider evaluateProvider
     */
    public function testEvaluateCustomerUser(
        array $consents,
        array $options,
        array $consentAcceptances,
        bool $expected
    ): void {
        $customerConsentAcceptances = [new ConsentAcceptance(), new ConsentAcceptance()];

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('consents')
            ->willReturn(true);

        $this->enabledConsentsProvider
            ->expects(self::once())
            ->method('getUnacceptedRequiredConsents')
            ->with(array_merge($customerConsentAcceptances, $consentAcceptances))
            ->willReturn($consents);

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn(new CustomerUser());
        $this->tokenStorage
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->consentAcceptanceProvider
            ->expects(self::once())
            ->method('getCustomerConsentAcceptances')
            ->willReturn($customerConsentAcceptances);

        $this->contextAccessor
            ->expects(self::any())
            ->method('hasValue')
            ->willReturn(true);

        $this->contextAccessor
            ->expects(self::any())
            ->method('getValue')
            ->willReturn($consentAcceptances);

        $this->condition->initialize($options);
        self::assertEquals($expected, $this->condition->evaluate([]));
    }

    /**
     * @dataProvider evaluateProvider
     */
    public function testEvaluateIsGuest(
        array $consents,
        array $options,
        array $consentAcceptances,
        bool $expected
    ): void {
        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('consents')
            ->willReturn(true);

        $this->enabledConsentsProvider
            ->expects(self::once())
            ->method('getUnacceptedRequiredConsents')
            ->with($consentAcceptances)
            ->willReturn($consents);

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn(null);
        $this->tokenStorage
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->consentAcceptanceProvider
            ->expects(self::never())
            ->method('getCustomerConsentAcceptances');

        $this->contextAccessor
            ->expects(self::any())
            ->method('hasValue')
            ->willReturn(true);

        $this->contextAccessor
            ->expects(self::any())
            ->method('getValue')
            ->willReturn($consentAcceptances);

        $this->condition->initialize($options);
        self::assertEquals($expected, $this->condition->evaluate([]));
    }

    public function testEvaluateFeatureDisabled(): void
    {
        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('consents')
            ->willReturn(false);

        $this->enabledConsentsProvider
            ->expects(self::never())
            ->method('getUnacceptedRequiredConsents');

        $this->tokenStorage
            ->expects(self::never())
            ->method('getToken');

        $this->condition->initialize([]);
        self::assertTrue($this->condition->evaluate([]));
    }

    public function evaluateProvider(): array
    {
        $propertyPath = new PropertyPath('acceptedConsents');
        $options = ['acceptedConsents' => $propertyPath];

        return [
            'has unaccepted consents w/o options' => [
                'consents' => [new Consent(), new Consent()],
                'options' => [],
                'consentAcceptances' => [],
                'expected' => false,
            ],
            'has unaccepted consents' => [
                'consents' => [new Consent(), new Consent()],
                'options' => $options,
                'consentAcceptances' => [new ConsentAcceptance(), new ConsentAcceptance()],
                'expected' => false,
            ],
            'all consents was accepted w/o options' => [
                'consents' => [],
                'options' => [],
                'consentAcceptances' => [],
                'expected' => true,
            ],
            'all consents was accepted' => [
                'consents' => [],
                'options' => $options,
                'consentAcceptances' => [new ConsentAcceptance(), new ConsentAcceptance()],
                'expected' => true,
            ],
        ];
    }
}
