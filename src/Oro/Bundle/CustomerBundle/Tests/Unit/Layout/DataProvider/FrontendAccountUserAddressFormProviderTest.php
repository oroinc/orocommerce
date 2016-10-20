<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;

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

    protected function setUp()
    {
        $this->mockFormFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')->getMock();
        $this->provider = new FrontendAccountUserAddressFormProvider($this->mockFormFactory);
    }

    public function testGetAddressFormWhileUpdate()
    {
        $this->actionTestWithId(1);
    }

    public function testGetAddressFormWhileCreate()
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

        $mockForm = $this->getMockBuilder('Symfony\Component\Form\FormInterface')->getMock();

        $this->mockFormFactory->expects($this->once())
            ->method('create')
            ->with(AccountUserTypedAddressType::NAME, $mockAccountUserAddress)
            ->willReturn($mockForm);

        $formAccessor = $this->provider->getAddressForm($mockAccountUserAddress, $mockAccountUser);

        $this->assertInstanceOf('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor', $formAccessor);

        $formAccessorSecondCall =  $this->provider->getAddressForm($mockAccountUserAddress, $mockAccountUser);
        $this->assertSame($formAccessor, $formAccessorSecondCall);

        $this->assertSame($formAccessor->getForm(), $formAccessorSecondCall->getForm());
    }
}
