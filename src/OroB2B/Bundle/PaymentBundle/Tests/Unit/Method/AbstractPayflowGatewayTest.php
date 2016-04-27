<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method;

use Symfony\Component\Routing\RouterInterface;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
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

    protected function setUp()
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

    protected function tearDown()
    {
        unset($this->configManager, $this->router, $this->gateway);
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

                        return count(array_diff($expected, $options)) === 0;
                    }
                )
            )
            ->willReturn($response);

        $this->configureRouter($transaction);

        $this->gateway->expects($this->any())
            ->method('setTestMode')
            ->with(true);


        $formActionExpects = $this->never();
        $formActionReturn = null;
        if (array_key_exists('formAction', $result)) {
            $formActionExpects = $this->once();
            $formActionReturn = $result['formAction'];
        }

        $this->gateway->expects($formActionExpects)
            ->method('getFormAction')
            ->willReturn($formActionReturn);


        $this->configureConfig($data['configs']);

        $this->assertEquals($result, $this->method->execute($transaction));
    }

    /**
     * @return array
     */
    abstract public function executeDataProvider();

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    protected function configureRouter(PaymentTransaction $paymentTransaction)
    {
        if ($paymentTransaction->getAction() !== 'purchase') {
            return;
        }

        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->withConsecutive(
                [
                    'orob2b_payment_callback_return',
                    [
                        'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                        'accessToken' => $paymentTransaction->getAccessToken(),
                    ],
                    true
                ],
                [
                    'orob2b_payment_callback_error',
                    [
                        'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                        'accessToken' => $paymentTransaction->getAccessToken(),
                    ],
                    true
                ]
            )
            ->willReturnArgument(0);
    }

    /**
     * @param array $configs
     */
    protected function configureConfig(array $configs = [])
    {
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
