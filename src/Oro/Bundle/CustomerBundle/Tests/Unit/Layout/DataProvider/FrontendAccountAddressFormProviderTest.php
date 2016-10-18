<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
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

    protected function setUp()
    {
        $this->mockFormFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')->getMock();
        $this->provider = new FrontendAccountAddressFormProvider($this->mockFormFactory);
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

        $mockForm = $this->getMockBuilder(FormInterface::class)->getMock();

        $this->mockFormFactory->expects($this->once())
            ->method('create')
            ->with(AccountTypedAddressType::NAME, $mockAccountUserAddress)
            ->willReturn($mockForm);

        $formAccessor = $this->provider->getAddressForm($mockAccountUserAddress, $mockAccountUser);

        $this->assertInstanceOf(FormAccessor::class, $formAccessor);

        $formAccessorSecondCall = $this->provider->getAddressForm($mockAccountUserAddress, $mockAccountUser);
        $this->assertSame($formAccessor, $formAccessorSecondCall);

        $this->assertSame($formAccessor->getForm(), $formAccessorSecondCall->getForm());
    }
}
