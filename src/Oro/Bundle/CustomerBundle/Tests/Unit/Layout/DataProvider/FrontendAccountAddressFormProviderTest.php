<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountAddress;
use Oro\Bundle\CustomerBundle\Form\Type\AccountTypedAddressType;
use Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendAccountAddressFormProvider;

class FrontendAccountAddressFormProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var FrontendAccountAddressFormProvider */
    protected $provider;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $mockFormFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UrlGeneratorInterface
     */
    protected $router;

    protected function setUp()
    {
        $this->mockFormFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')->getMock();
        $this->router = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $this->provider = new FrontendAccountAddressFormProvider($this->mockFormFactory, $this->router);
    }

    public function testGetAddressFormViewWhileUpdate()
    {
        $this->actionTestWithId(1);
    }

    public function testGetAddressFormViewWhileCreate()
    {
        $this->actionTestWithId();
    }

    /**
     * @param int|null $id
     */
    private function actionTestWithId($id = null)
    {
        /** @var AccountAddress|\PHPUnit_Framework_MockObject_MockObject $mockAccountUserAddress */
        $mockAccountUserAddress = $this->getMockBuilder(AccountAddress::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockAccountUserAddress->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        /** @var Account|\PHPUnit_Framework_MockObject_MockObject $mockAccountUser */
        $mockAccountUser = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockAccountUser->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $mockFormView = $this->getMock(FormView::class);

        $mockForm = $this->getMockBuilder(FormInterface::class)->getMock();
        $mockForm->expects($this->once())
            ->method('createView')
            ->willReturn($mockFormView);

        $this->mockFormFactory->expects($this->once())
            ->method('create')
            ->with(AccountTypedAddressType::NAME, $mockAccountUserAddress)
            ->willReturn($mockForm);

        $form = $this->provider->getAddressFormView($mockAccountUserAddress, $mockAccountUser);

        $this->assertInstanceOf(FormView::class, $form);

        $formSecondCall = $this->provider->getAddressFormView($mockAccountUserAddress, $mockAccountUser);
        $this->assertSame($form, $formSecondCall);
    }
}
