<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Layout\DataProvider;

use OroB2B\Bundle\PaymentBundle\Layout\DataProvider\PaymentMethodWidgetProvider;

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

    public function testGetIdentifier()
    {
        $this->assertSame('orob2b_payment_method_widget_provider', $this->provider->getIdentifier());
    }

    public function testGetPaymentMethodWidgetName()
    {
        $entity = $this->getMock('OroB2B\Bundle\PaymentBundle\Entity\PaymentMethodAwareInterface');
        $entity->expects($this->once())
            ->method('getPaymentMethod')
            ->willReturn('payment_method');

        $prefix = 'test_prefix';

        $this->assertSame(
            '_payment_method_test_prefix_widget',
            $this->provider->getPaymentMethodWidgetName($entity, $prefix)
        );
    }

    public function testGetPaymentMethodWidgetNameEmpty()
    {
        $entity = new \stdClass();
        $prefix = 'test_prefix';

        $this->assertSame('', $this->provider->getPaymentMethodWidgetName($entity, $prefix));
    }
}
