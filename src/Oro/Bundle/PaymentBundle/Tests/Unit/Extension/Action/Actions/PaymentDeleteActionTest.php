<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Extension\Action\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\PaymentBundle\Extension\Action\Actions\PaymentDeleteAction;

class PaymentDeleteActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider setOptionsDataProvider
     */
    public function testSetOptions(array $options, ActionConfiguration $expected)
    {
        $paymentDeleteAction = new PaymentDeleteAction();
        $paymentDeleteAction->setOptions(ActionConfiguration::create($options));
        $this->assertEquals($expected, $paymentDeleteAction->getOptions());
    }

    public function setOptionsDataProvider(): array
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
