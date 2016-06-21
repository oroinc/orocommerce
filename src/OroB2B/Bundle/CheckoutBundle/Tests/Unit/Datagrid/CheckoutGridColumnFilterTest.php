<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use OroB2B\Bundle\AccountBundle\Security\AccountUserProvider;
use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutGridColumnFilter;

class CheckoutGridColumnFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutGridColumnFilter
     */
    protected $testable;

    /**
     * @var AccountUserProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $provider;


    public function testOnBuildBefore()
    {
        $this->provider = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Security\AccountUserProvider')
                               ->disableOriginalConstructor()
                               ->getMock();

        $event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\BuildBefore')
                      ->disableOriginalConstructor()
                      ->getMock();

        $configObject = $this->getMockBuilder('Oro\Component\Config\Common\ConfigObject')
                             ->disableOriginalConstructor()
                             ->getMock();

        $exampleColumns = [
            'accountUserName' => 'Dummy',
            'createdAt'       => '2016-01-01'
        ];

        $configObject->expects($this->at(0))
                     ->method('offsetGetByPath')
                     ->with('[columns]')
                     ->willReturn($exampleColumns);

        $configObject->expects($this->at(1))
                     ->method('offsetUnsetByPath')
                     ->with('[columns][accountUserName]');

        $configObject->expects($this->at(2))
                     ->method('offsetUnsetByPath')
                     ->with('[sorters][columns][accountUserName]');

        $event->expects($this->once())
              ->method('getConfig')
              ->willReturn($configObject);

        $this->provider->expects($this->once())
                       ->method('isGrantedViewLocal')
                       ->willReturn(false);

        $this->testable = new CheckoutGridColumnFilter($this->provider);
        $this->testable->onBuildBefore($event);
    }
}
