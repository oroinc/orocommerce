<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Client\ClientInterface;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Gateway;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\OptionsResolver;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Partner;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Processor\ProcessorRegistry;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request\RequestRegistry;

class GatewayTest extends \PHPUnit_Framework_TestCase
{
    /** @var Gateway */
    protected $gateway;

    /** @var ProcessorRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $processorRegistry;

    /** @var RequestRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestRegistry;

    /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $client;

    protected function setUp()
    {
        $this->processorRegistry = $this->getMock(
            'OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Processor\ProcessorRegistry'
        );

        $this->requestRegistry = $this
            ->getMockBuilder('OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request\RequestRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->client = $this->getMock('OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Client\ClientInterface');

        $this->gateway = new Gateway($this->client, $this->requestRegistry, $this->processorRegistry);
    }

    protected function tearDown()
    {
        unset($this->gateway, $this->client, $this->requestRegistry, $this->processorRegistry);
    }

    public function testRequest()
    {
        $action = 'ACTION';
        $options = [
            Partner::PARTNER => 'PARTNER',
        ];

        $request = $this->getMock('OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request\RequestInterface');
        $request
            ->expects($this->once())
            ->method('configureOptions')
            ->with(
                $this->isInstanceOf('OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\OptionsResolver')
            )
            ->willReturnCallback(function (OptionsResolver $resolver) {
                $resolver->setDefined(Partner::PARTNER);
            });

        $this->requestRegistry
            ->expects($this->once())
            ->method('getRequest')
            ->with($action)
            ->willReturn($request);

        $processor = $this->getMock('OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Processor\ProcessorInterface');
        $processor
            ->expects($this->once())
            ->method('configureOptions')
            ->with(
                $this->isInstanceOf('OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\OptionsResolver')
            );

        $this->processorRegistry
            ->expects($this->once())
            ->method('getProcessor')
            ->with($options[Partner::PARTNER])
            ->willReturn($processor);

        $responseData = ['response' => 'data'];
        $this->client
            ->expects($this->once())
            ->method('send')
            ->with(Gateway::PILOT_HOST_ADDRESS)
            ->willReturn($responseData);

        $this->gateway->setTestMode(true);
        $response = $this->gateway->request($action, $options);

        $this->assertInstanceOf('OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\ResponseInterface', $response);
        $this->assertEquals($responseData, $response->getData());
    }

    public function testGetFormAction()
    {
        $this->gateway->setTestMode(true);
        $this->assertEquals(Gateway::PILOT_FORM_ACTION, $this->gateway->getFormAction());

        $this->gateway->setTestMode(false);
        $this->assertEquals(Gateway::PRODUCTION_FORM_ACTION, $this->gateway->getFormAction());
    }
}
