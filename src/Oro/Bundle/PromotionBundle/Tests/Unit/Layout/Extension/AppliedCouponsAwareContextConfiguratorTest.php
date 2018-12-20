<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Layout\Extension\AppliedCouponsAwareContextConfigurator;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Checkout;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Oro\Component\Layout\LayoutContext;

class AppliedCouponsAwareContextConfiguratorTest extends \PHPUnit\Framework\TestCase
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
    public function contextDataProvider(): array
    {
        $sourceEntity = $this->createMock(QuoteDemand::class);
        /** @var CheckoutSource|\PHPUnit\Framework\MockObject\MockObject $checkoutSource */
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects($this->any())
            ->method('getEntity')
            ->willReturn($sourceEntity);
        $checkoutWithQuote = new Checkout();
        $checkoutWithQuote->setSource($checkoutSource);

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
            'checkout no checkout interface' => [
                'checkout', new \stdClass(), false
            ],
            'checkout no applied coupons interface' => [
                'checkout', $this->createMock(CheckoutInterface::class), false
            ],
            'checkout with quote as source' => [
                'checkout', $checkoutWithQuote, false
            ],
            'checkout source entity is instance of' => [
                'checkout', new Checkout(), true
            ],
        ];
    }
}
