<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method;

use Symfony\Component\Routing\RouterInterface;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\Response;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Gateway;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

abstract class AbstractPayflowGatewayTest extends \PHPUnit_Framework_TestCase
{
    use ConfigTestTrait, EntityTrait;

    /** @var Gateway|\PHPUnit_Framework_MockObject_MockObject */
    protected $gateway;

    /** @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var PaymentMethodInterface */
    protected $method;

    protected function setMocks()
    {
        $this->gateway = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Gateway')
            ->disableOriginalConstructor()
            ->getMock();

        $this->router = $this->getMockBuilder('Symfony\Component\Routing\RouterInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param array $data
     * @param array $result
     */
    public function testExecute($data, $result)
    {
        /** @var PaymentTransaction $transaction */
        $transaction = $this->getEntity(
            'OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction',
            $data['transactionData']
        );

        if (!empty($data['sourceTransactionData'])) {
            /** @var PaymentTransaction $sourceTransaction */
            $sourceTransaction = $this->getEntity(
                'OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction',
                $data['sourceTransactionData']
            );
            $transaction->setSourcePaymentTransaction($sourceTransaction);
        }

        $response = new Response($data['responseData']);

        $this->gateway->expects($this->any())
            ->method('request')
            ->with(
                $data['gatewayAction'],
                $this->callback(
                    function ($options) use ($data) {
                        unset($options['SECURETOKENID']);
                        $expected = $data['requestOptions'];

                        return(count($options) == count($expected) && $options == $expected);
                    }
                )
            )
            ->willReturn($response);

        $this->router->expects($this->any())
            ->method('generate')
            ->willReturnArgument(0);

        $this->gateway->expects($this->any())
            ->method('setTestMode');

        $this->gateway->expects($this->any())
            ->method('getFormAction')
            ->willReturn('test_form_action');

        $this->setExecuteConfigs($data['configs']);

        $this->assertEquals($result, $this->method->execute($transaction));
    }

    /**
     * @param array $configs
     */
    protected function setExecuteConfigs($configs = [])
    {
        $configs = array_merge(
            [
                Configuration::PAYFLOW_GATEWAY_VENDOR_KEY => 'test_vendor',
                Configuration::PAYFLOW_GATEWAY_USER_KEY => 'test_user',
                Configuration::PAYFLOW_GATEWAY_PASSWORD_KEY => 'test_password',
                Configuration::PAYFLOW_GATEWAY_PARTNER_KEY => 'test_partner',
                Configuration::PAYFLOW_GATEWAY_TEST_MODE_KEY => true,
            ],
            $configs
        );

        $map = [];
        array_walk(
            $configs,
            function ($val, $key) use (&$map) {
                $map[] = [$this->getConfigKey($key), false, false, $val];
            }
        );

        $this->configManager->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));
    }
}
