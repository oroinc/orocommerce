<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Checker;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Checker\ShippingMethodEnabledByIdentifierChecker;

class ShippingMethodEnabledByIdentifierCheckerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingMethodInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $method;

    /**
     * @var ShippingMethodRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ShippingMethodEnabledByIdentifierChecker
     */
    protected $shippingMethodEnabledByIdentifierChecker;

    protected function setUp()
    {
        $this->method = $this->createMock(ShippingMethodInterface::class);

        $this->registry = $this->createMock(ShippingMethodRegistry::class);

        $this->shippingMethodEnabledByIdentifierChecker = new ShippingMethodEnabledByIdentifierChecker($this->registry);
    }

    public function testIsEnabledForEnabledMethod()
    {
        $identifier = 'shipping_method_1';

        $this->method
            ->expects(static::once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->registry
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

        $this->registry
            ->expects(static::any())
            ->method('getShippingMethod')
            ->with($identifier)
            ->willReturn($this->method);

        $this->assertFalse($this->shippingMethodEnabledByIdentifierChecker->isEnabled($identifier));
    }

    public function testIsEnabledForNotExistingMethod()
    {
        $identifier = 'shipping_method_1';

        $this->registry
            ->expects(static::any())
            ->method('getShippingMethod')
            ->with($identifier)
            ->willReturn(null);

        $this->assertFalse($this->shippingMethodEnabledByIdentifierChecker->isEnabled($identifier));
    }
}
