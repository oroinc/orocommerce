<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserAddress;
use Oro\Bundle\CustomerBundle\Form\Type\AccountUserTypedAddressType;
use Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendAccountUserAddressFormProvider;

class FrontendAccountUserAddressFormProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var FrontendAccountUserAddressFormProvider */
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

        $this->provider = new FrontendAccountUserAddressFormProvider($this->mockFormFactory, $this->router);
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
        /** @var AccountUserAddress|\PHPUnit_Framework_MockObject_MockObject $mockAccountUserAddress */
        $mockAccountUserAddress = $this->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\AccountUserAddress')
            ->disableOriginalConstructor()
            ->getMock();

        $mockAccountUserAddress->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        /** @var AccountUser|\PHPUnit_Framework_MockObject_MockObject $mockAccountUser */
        $mockAccountUser = $this->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\AccountUser')
            ->disableOriginalConstructor()
            ->getMock();

        $mockAccountUser->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $mockFormView = $this->getMock(FormView::class);

        $mockForm = $this->getMockBuilder('Symfony\Component\Form\FormInterface')->getMock();
        $mockForm->expects($this->once())
            ->method('createView')
            ->willReturn($mockFormView);

        $this->mockFormFactory->expects($this->once())
            ->method('create')
            ->with(AccountUserTypedAddressType::NAME, $mockAccountUserAddress)
            ->willReturn($mockForm);

        $form = $this->provider->getAddressFormView($mockAccountUserAddress, $mockAccountUser);

        $this->assertInstanceOf(FormView::class, $form);

        $formSecondCall =  $this->provider->getAddressFormView($mockAccountUserAddress, $mockAccountUser);
        $this->assertSame($form, $formSecondCall);
    }
}
