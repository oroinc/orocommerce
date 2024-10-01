<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Oro\Bundle\CheckoutBundle\Condition\IsMultiShippingEnabled;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;

class IsMultiShippingEnabledTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var IsMultiShippingEnabled */
    private $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->condition = new IsMultiShippingEnabled($this->configProvider);
    }

    public function testIsConditionAllowedWhenMultiShippingEnabled(): void
    {
        $this->configProvider->expects(self::once())
            ->method('isMultiShippingEnabled')
            ->willReturn(true);

        self::assertTrue($this->condition->evaluate([]));
    }

    public function testIsConditionAllowedWhenMultiShippingDisabled(): void
    {
        $this->configProvider->expects(self::once())
            ->method('isMultiShippingEnabled')
            ->willReturn(false);

        self::assertFalse($this->condition->evaluate([]));
    }

    public function testGetName(): void
    {
        self::assertEquals('is_multishipping_enabled', $this->condition->getName());
    }

    public function testToArray(): void
    {
        self::assertEquals(['@is_multishipping_enabled' => null], $this->condition->toArray());
    }

    public function testCompile(): void
    {
        self::assertEquals(
            '$expressionFactory->create(\'is_multishipping_enabled\', [])',
            $this->condition->compile('$expressionFactory')
        );
    }
}
