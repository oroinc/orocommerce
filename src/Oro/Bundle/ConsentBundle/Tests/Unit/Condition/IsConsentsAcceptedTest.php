<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Condition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Condition\ToStringStub;
use Oro\Bundle\ConsentBundle\Condition\CheckoutHasUnacceptedConsents;
use Oro\Bundle\ConsentBundle\Condition\IsConsentsAccepted;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Extractor\CustomerUserExtractor;
use Oro\Bundle\ConsentBundle\Provider\ConsentDataProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\Testing\Unit\EntityTrait;

class IsConsentsAcceptedTest extends \PHPUnit\Framework\TestCase
{
    /** @var IsConsentsAccepted */
    private $condition;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var ConsentDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $consentsProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->consentsProvider = $this->createMock(ConsentDataProvider::class);

        $this->condition = new IsConsentsAccepted($this->featureChecker, $this->consentsProvider);
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
    public function testEvaluateFeatureEnabled(array $consents, $expected)
    {
        $this->featureChecker
            ->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents')
            ->willReturn(true);

        $this->consentsProvider
            ->expects($this->once())
            ->method('getNotAcceptedRequiredConsentData')
            ->willReturn($consents);

        $this->condition->initialize([]);
        $this->assertEquals($expected, $this->condition->evaluate([]));
    }

    public function testEvaluateFeatureDisabled()
    {
        $this->featureChecker
            ->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents')
            ->willReturn(false);

        $this->consentsProvider
            ->expects($this->never())
            ->method('getNotAcceptedRequiredConsentData');

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
