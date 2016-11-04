<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Component\Testing\Unit\EntityTrait;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserAddress;
use Oro\Bundle\CustomerBundle\Form\Type\AccountUserTypedAddressType;
use Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendAccountUserAddressFormProvider;

class FrontendAccountUserAddressFormProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var FrontendAccountUserAddressFormProvider */
    protected $provider;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $mockFormFactory;

    /** @var UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    protected function setUp()
    {
        $this->mockFormFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')->getMock();
        $this->router = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $this->provider = new FrontendAccountUserAddressFormProvider($this->mockFormFactory, $this->router);
    }

    public function testGetAddressFormViewWhileUpdate()
    {
        $action = 'form_action';

        $accountUser = $this->getEntity(AccountUser::class, ['id' => 1]);
        $accountUserAddress = $this->getEntity(AccountUserAddress::class, ['id' => 2]);

        $formView = $this->getMock(FormView::class);

        $form = $this->getMock(FormInterface::class);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->mockFormFactory
            ->expects($this->once())
            ->method('create')
            ->with(AccountUserTypedAddressType::NAME, $accountUserAddress, ['action' => $action])
            ->willReturn($form);

        $this->router
            ->expects($this->exactly(2))
            ->method('generate')
            ->with(
                FrontendAccountUserAddressFormProvider::ACCOUNT_USER_ADDRESS_UPDATE_ROUTE_NAME,
                ['id' => 2, 'entityId' => 1]
            )
            ->willReturn($action);

        $result = $this->provider->getAddressFormView($accountUserAddress, $accountUser);

        $this->assertInstanceOf(FormView::class, $result);

        $resultSecondCall =  $this->provider->getAddressFormView($accountUserAddress, $accountUser);
        $this->assertSame($result, $resultSecondCall);
    }

    public function testGetAddressFormWhileUpdate()
    {
        $action = 'form_action';

        $accountUser = $this->getEntity(AccountUser::class, ['id' => 1]);
        $accountUserAddress = $this->getEntity(AccountUserAddress::class, ['id' => 2]);

        $form = $this->getMock(FormInterface::class);

        $this->mockFormFactory
            ->expects($this->once())
            ->method('create')
            ->with(AccountUserTypedAddressType::NAME, $accountUserAddress, ['action' => $action])
            ->willReturn($form);

        $this->router
            ->expects($this->exactly(2))
            ->method('generate')
            ->with(
                FrontendAccountUserAddressFormProvider::ACCOUNT_USER_ADDRESS_UPDATE_ROUTE_NAME,
                ['id' => 2, 'entityId' => 1]
            )
            ->willReturn($action);

        $result = $this->provider->getAddressForm($accountUserAddress, $accountUser);

        $this->assertInstanceOf(FormInterface::class, $result);

        $resultSecondCall =  $this->provider->getAddressForm($accountUserAddress, $accountUser);
        $this->assertSame($result, $resultSecondCall);
    }

    public function testGetAddressFormViewWhileCreate()
    {
        $action = 'form_action';

        $accountUser = $this->getEntity(AccountUser::class, ['id' => 1]);
        $accountUserAddress = $this->getEntity(AccountUserAddress::class);

        $formView = $this->getMock(FormView::class);

        $form = $this->getMock(FormInterface::class);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->mockFormFactory
            ->expects($this->once())
            ->method('create')
            ->with(AccountUserTypedAddressType::NAME, $accountUserAddress, ['action' => $action])
            ->willReturn($form);

        $this->router
            ->expects($this->exactly(2))
            ->method('generate')
            ->with(
                FrontendAccountUserAddressFormProvider::ACCOUNT_USER_ADDRESS_CREATE_ROUTE_NAME,
                ['entityId' => 1]
            )
            ->willReturn($action);

        $result = $this->provider->getAddressFormView($accountUserAddress, $accountUser);

        $this->assertInstanceOf(FormView::class, $result);

        $resultSecondCall =  $this->provider->getAddressFormView($accountUserAddress, $accountUser);
        $this->assertSame($result, $resultSecondCall);
    }

    public function testGetAddressFormWhileCreate()
    {
        $action = 'form_action';

        $accountUser = $this->getEntity(AccountUser::class, ['id' => 1]);
        $accountUserAddress = $this->getEntity(AccountUserAddress::class);

        $form = $this->getMock(FormInterface::class);

        $this->mockFormFactory
            ->expects($this->once())
            ->method('create')
            ->with(AccountUserTypedAddressType::NAME, $accountUserAddress, ['action' => $action])
            ->willReturn($form);

        $this->router
            ->expects($this->exactly(2))
            ->method('generate')
            ->with(FrontendAccountUserAddressFormProvider::ACCOUNT_USER_ADDRESS_CREATE_ROUTE_NAME, ['entityId' => 1])
            ->willReturn($action);

        $result = $this->provider->getAddressForm($accountUserAddress, $accountUser);

        $this->assertInstanceOf(FormInterface::class, $result);

        $resultSecondCall =  $this->provider->getAddressForm($accountUserAddress, $accountUser);
        $this->assertSame($result, $resultSecondCall);
    }
}
