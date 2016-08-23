<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodAwareInterface;
use Oro\Bundle\PaymentBundle\Layout\DataProvider\PaymentMethodWidgetProvider;

class PaymentMethodWidgetProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var PaymentMethodWidgetProvider */
    private $provider;

    protected function setUp()
    {
        $this->provider = new PaymentMethodWidgetProvider();
    }

    protected function tearDown()
    {
        unset($this->provider);
    }

    public function testGetPaymentMethodWidgetName()
    {
        $entity = $this->getMock(PaymentMethodAwareInterface::class);
        $entity->expects($this->once())
            ->method('getPaymentMethod')
            ->willReturn('payment_method');

        $prefix = 'test_prefix';

        $this->assertSame(
            '_payment_method_test_prefix_widget',
            $this->provider->getPaymentMethodWidgetName($entity, $prefix)
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object "stdClass" must implement interface "Oro\Bundle\PaymentBundle\Entity\PaymentMethodAwareInterface"
     */
    // @codingStandardsIgnoreEnd
    public function testGetPaymentMethodWidgetNameEmpty()
    {
        $entity = new \stdClass();
        $prefix = 'test_prefix';

        $this->provider->getPaymentMethodWidgetName($entity, $prefix);
    }
}
