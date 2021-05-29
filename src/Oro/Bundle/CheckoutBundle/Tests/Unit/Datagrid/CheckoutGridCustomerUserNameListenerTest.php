<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridCustomerUserNameListener;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class CheckoutGridCustomerUserNameListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testOnBuildBefore()
    {
        $config = $this->createMock(DatagridConfiguration::class);
        $config->expects($this->once())
            ->method('offsetGetByPath')
            ->with('[columns]')
            ->willReturn([
                'customerUserName' => 'Dummy',
                'createdAt'        => '2016-01-01'
            ]);
        $config->expects($this->exactly(3))
            ->method('offsetUnsetByPath')
            ->withConsecutive(
                ['[columns][customerUserName]'],
                ['[sorters][columns][customerUserName]'],
                ['[filters][columns][customerUserName]']
            );

        $provider = $this->createMock(CustomerUserProvider::class);
        $provider->expects($this->once())
            ->method('isGrantedViewCustomerUser')
            ->with(Checkout::class)
            ->willReturn(false);

        $listener = new CheckoutGridCustomerUserNameListener($provider);
        $listener->onBuildBefore(new BuildBefore($this->createMock(DatagridInterface::class), $config));
    }
}
