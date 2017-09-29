<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Layout\Extension\AppliedCouponsAwareContextConfigurator;
use Oro\Component\Layout\LayoutContext;

class AppliedCouponsAwareContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider contextDataProvider
     * @param string $key
     * @param mixed $value
     * @param bool $isAware
     */
    public function testConfigureContext($key, $value, $isAware)
    {
        $context = new LayoutContext();
        $context->data()->set($key, $value);

        $contextConfigurator = new AppliedCouponsAwareContextConfigurator();
        $contextConfigurator->configureContext($context);

        $this->assertTrue($context->has('isAppliedCouponsAware'));
        $this->assertSame($isAware, $context->get('isAppliedCouponsAware'));
    }

    /**
     * @return array
     */
    public function contextDataProvider()
    {
        $unsupportedCheckout = $this->createMock(Checkout::class);
        $unsupportedCheckout->expects($this->any())
            ->method('getSourceEntity')
            ->willReturn(new \stdClass());
        $supportedCheckout = $this->createMock(Checkout::class);
        $supportedCheckout->expects($this->any())
            ->method('getSourceEntity')
            ->willReturn($this->createMock(AppliedCouponsAwareInterface::class));

        return [
            'no required keys' => [
                'some', 'test', false
            ],
            'entity is not instanceof' => [
                'entity', new \stdClass(), false
            ],
            'entity is instanceof' => [
                'entity', $this->createMock(AppliedCouponsAwareInterface::class), true
            ],
            'checkout source entity is not instance of' => [
                'checkout', $unsupportedCheckout, false
            ],
            'checkout source entity is instance of' => [
                'checkout', $supportedCheckout, true
            ],
        ];
    }
}
