<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Checker;

use Oro\Bundle\ShippingBundle\Checker\ShippingMethodEnabledByIdentifierChecker;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class ShippingMethodEnabledByIdentifierCheckerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShippingMethodInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $method;

    /**
     * @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shippingMethodProvider;

    /**
     * @var ShippingMethodEnabledByIdentifierChecker
     */
    protected $shippingMethodEnabledByIdentifierChecker;

    protected function setUp(): void
    {
        $this->method = $this->createMock(ShippingMethodInterface::class);

        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);

        $this->shippingMethodEnabledByIdentifierChecker = new ShippingMethodEnabledByIdentifierChecker(
            $this->shippingMethodProvider
        );
    }

    public function testIsEnabledForEnabledMethod()
    {
        $identifier = 'shipping_method_1';

        $this->method
            ->expects(static::once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->shippingMethodProvider
            ->expects(static::any())
            ->method('getShippingMethod')
            ->with($identifier)
            ->willReturn($this->method);

        $this->assertTrue($this->shippingMethodEnabledByIdentifierChecker->isEnabled($identifier));
    }

    public function testIsEnabledForDisabledMethod()
    {
        $identifier = 'shipping_method_1';

        $this->method
            ->expects(static::once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->shippingMethodProvider
            ->expects(static::any())
            ->method('getShippingMethod')
            ->with($identifier)
            ->willReturn($this->method);

        $this->assertFalse($this->shippingMethodEnabledByIdentifierChecker->isEnabled($identifier));
    }

    public function testIsEnabledForNotExistingMethod()
    {
        $identifier = 'shipping_method_1';

        $this->shippingMethodProvider
            ->expects(static::any())
            ->method('getShippingMethod')
            ->with($identifier)
            ->willReturn(null);

        $this->assertFalse($this->shippingMethodEnabledByIdentifierChecker->isEnabled($identifier));
    }
}
