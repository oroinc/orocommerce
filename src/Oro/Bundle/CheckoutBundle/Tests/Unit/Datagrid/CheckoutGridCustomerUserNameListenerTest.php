<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;
use Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridCustomerUserNameListener;

class CheckoutGridCustomerUserNameListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutGridCustomerUserNameListener
     */
    protected $testable;

    /**
     * @var CustomerUserProvider|\PHPUnit_Framework_MockObject_MockObject
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
                       ->method('isGrantedViewLocal')
                       ->willReturn(false);

        $this->testable = new CheckoutGridCustomerUserNameListener($this->provider);
        $this->testable->onBuildBefore($event);
    }
}
