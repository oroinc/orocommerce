<?php

namespace OroB2B\src\OroB2B\Bundle\PaymentBundle\Tests\Unit\Extension\Action\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;

use OroB2B\Bundle\PaymentBundle\Extension\Action\Actions\PaymentDeleteAction;

class PaymentDeleteActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $options
     * @param ActionConfiguration $expected
     * @dataProvider setOptionsDataProvider
     */
    public function testSetOptions(array $options, ActionConfiguration $expected)
    {
        $paymentDeleteAction = new PaymentDeleteAction();
        $paymentDeleteAction->setOptions(ActionConfiguration::create($options));
        $this->assertEquals($expected, $paymentDeleteAction->getOptions());
    }

    /**
     * @return array
     */
    public function setOptionsDataProvider()
    {
        $link = 'http://localhost';
        return [
            'without confirmation' => [
                'options' => [
                    'link' => $link
                ],
                'expected' => ActionConfiguration::create([
                    'link' => $link,
                    'confirmation' => true
                ])
            ],
            'with confirmation' => [
                'options' => [
                    'confirmation' => true,
                    'link' => $link
                ],
                'expected' => ActionConfiguration::create([
                    'link' => $link,
                    'confirmation' => true
                ])
            ]
        ];
    }
}
