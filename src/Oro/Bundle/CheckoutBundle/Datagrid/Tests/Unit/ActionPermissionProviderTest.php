<?php

namespace Oro\Bundle\CheckoutBundle\Datagrid\Tests\Unit;

use Oro\Bundle\CheckoutBundle\Datagrid\ActionPermissionProvider;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ActionPermissionProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var CheckoutRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $checkoutRepository;

    /** @var ActionPermissionProvider */
    protected $provider;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)->disableOriginalConstructor()->getMock();

        $this->checkoutRepository = $this->getMockBuilder(CheckoutRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new ActionPermissionProvider($this->securityFacade, $this->checkoutRepository);
    }

    /**
     * @param Checkout $checkout
     * @param AccountUser $accountUser
     * @param array $expectedResult
     *
     * @dataProvider getActionPermissionsProvider
     */
    public function testGetActionPermissions(Checkout $checkout, AccountUser $accountUser, array $expectedResult)
    {
        $this->checkoutRepository->expects($this->once())->method('find')->willReturn($checkout);

        $this->securityFacade->expects($this->once())->method('getLoggedUser')->willReturn($accountUser);

        $this->assertEquals($expectedResult, $this->provider->getActionPermissions(new ResultRecord(['id' => 10])));
    }

    /**
     * @return array
     */
    public function getActionPermissionsProvider()
    {
        $accountUser = new AccountUser();

        return [
            'own checkout' => [
                'checkout' => (new Checkout())->setAccountUser($accountUser),
                'accountUser' => $accountUser,
                'expectedResult' => ['view' => true]
            ],
            'other checkout' => [
                'checkout' => (new Checkout())->setAccountUser($accountUser),
                'accountUser' => new AccountUser(),
                'expectedResult' => ['view' => false]
            ],
        ];
    }
}
