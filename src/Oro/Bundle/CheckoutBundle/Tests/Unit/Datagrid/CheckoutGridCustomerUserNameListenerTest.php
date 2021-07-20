<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridCustomerUserNameListener;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;

class CheckoutGridCustomerUserNameListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CheckoutGridCustomerUserNameListener
     */
    protected $testable;

    /**
     * @var CustomerUserProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $provider;

    public function testOnBuildBefore()
    {
        $this->provider = $this->getMockBuilder('Oro\Bundle\CustomerBundle\Security\CustomerUserProvider')
                               ->disableOriginalConstructor()
                               ->getMock();

        $event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\BuildBefore')
                      ->disableOriginalConstructor()
                      ->getMock();

        $configObject = $this->getMockBuilder('Oro\Component\Config\Common\ConfigObject')
                             ->disableOriginalConstructor()
                             ->getMock();

        $exampleColumns = [
            'customerUserName' => 'Dummy',
            'createdAt'       => '2016-01-01'
        ];

        $configObject->expects($this->at(0))
                     ->method('offsetGetByPath')
                     ->with('[columns]')
                     ->willReturn($exampleColumns);

        $configObject->expects($this->at(1))
                     ->method('offsetUnsetByPath')
                     ->with('[columns][customerUserName]');

        $configObject->expects($this->at(2))
                     ->method('offsetUnsetByPath')
                     ->with('[sorters][columns][customerUserName]');

        $event->expects($this->once())
              ->method('getConfig')
              ->willReturn($configObject);

        $this->provider->expects($this->once())
                       ->method('isGrantedViewCustomerUser')
                       ->with(Checkout::class)
                       ->willReturn(false);

        $this->testable = new CheckoutGridCustomerUserNameListener($this->provider);
        $this->testable->onBuildBefore($event);
    }
}
