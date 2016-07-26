<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextDataCollection;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountTypedAddressType;
use OroB2B\Bundle\AccountBundle\Layout\DataProvider\FrontendAccountAddressFormDataProvider;

class FrontendAccountAddressFormDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var FrontendAccountAddressFormDataProvider */
    protected $provider;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $mockFormFactory;

    protected function setUp()
    {
        $this->mockFormFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')->getMock();
        $this->provider = new FrontendAccountAddressFormDataProvider($this->mockFormFactory);
    }

    public function testGetDataWhileUpdate()
    {
        $this->actionTestWithId(1);
    }

    public function testGetDataWhileCreate()
    {
        $this->actionTestWithId();
    }

    /**
     * @param int|null $id
     */
    private function actionTestWithId($id = null)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ContextInterface $mockContextInterface */
        $mockContextInterface = $this->getMockBuilder(ContextInterface::class)
            ->getMock();

        $mockDataCollection = $this->getMockBuilder(ContextDataCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockContextInterface->expects($this->exactly(3))
            ->method('data')
            ->willReturn($mockDataCollection);

        $mockAccountUserAddress = $this->getMockBuilder(AccountAddress::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockAccountUserAddress->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        $mockDataCollection->expects($this->at(0))
            ->method('get')
            ->with('entity')
            ->willReturn($mockAccountUserAddress);

        $mockAccountUser = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockDataCollection->expects($this->at(1))
            ->method('get')
            ->with('account')
            ->willReturn($mockAccountUser);

        $mockAccountUser->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $mockForm = $this->getMockBuilder(FormInterface::class)->getMock();

        $this->mockFormFactory->expects($this->once())
            ->method('create')
            ->with(AccountTypedAddressType::NAME, $mockAccountUserAddress)
            ->willReturn($mockForm);

        $mockDataCollection->expects($this->at(2))
            ->method('get')
            ->with('entity')
            ->willReturn($mockAccountUserAddress);

        $formAccessor = $this->provider->getData($mockContextInterface);

        $this->assertInstanceOf(FormAccessor::class, $formAccessor);

        $formAccessorSecondCall = $this->provider->getData($mockContextInterface);
        $this->assertSame($formAccessor, $formAccessorSecondCall);

        $this->assertSame($formAccessor->getForm(), $formAccessorSecondCall->getForm());
    }
}
