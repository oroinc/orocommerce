<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Layout\Extension\AppliedCouponsAwareContextConfigurator;
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
            'checkout source entity is instance of' => [
                'checkout', $this->createMock(AppliedCouponsAwareInterface::class), true
            ],
        ];
    }
}
