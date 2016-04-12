<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserTypedAddressType;
use OroB2B\Bundle\AccountBundle\Layout\DataProvider\FrontendAccountUserAddressFormDataProvider;

class FrontendAccountUserAddressFormDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var FrontendAccountUserAddressFormDataProvider */
    protected $provider;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $mockFormFactory;

    protected function setUp()
    {
        $this->mockFormFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')->getMock();
        $this->provider = new FrontendAccountUserAddressFormDataProvider($this->mockFormFactory);
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
        $mockContextInterface = $this->getMockBuilder('Oro\Component\Layout\ContextInterface')
            ->getMock();

        $mockDataCollection = $this->getMockBuilder('Oro\Component\Layout\ContextDataCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $mockContextInterface->expects($this->exactly(3))
            ->method('data')
            ->willReturn($mockDataCollection);

        $mockAccountUserAddress = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress')
            ->disableOriginalConstructor()
            ->getMock();

        $mockAccountUserAddress->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        $mockDataCollection->expects($this->at(0))
            ->method('get')
            ->with('entity')
            ->willReturn($mockAccountUserAddress);

        $mockAccountUser = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\AccountUser')
            ->disableOriginalConstructor()
            ->getMock();

        $mockDataCollection->expects($this->at(1))
            ->method('get')
            ->with('accountUser')
            ->willReturn($mockAccountUser);

        $mockAccountUser->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $mockForm = $this->getMockBuilder('Symfony\Component\Form\FormInterface')->getMock();

        $this->mockFormFactory->expects($this->once())
            ->method('create')
            ->with(AccountUserTypedAddressType::NAME, $mockAccountUserAddress)
            ->willReturn($mockForm);

        $mockDataCollection->expects($this->at(2))
            ->method('get')
            ->with('entity')
            ->willReturn($mockAccountUserAddress);

        $formAccessor = $this->provider->getData($mockContextInterface);

        $this->assertInstanceOf('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor', $formAccessor);

        $formAccessorSecondCall =  $this->provider->getData($mockContextInterface);
        $this->assertSame($formAccessor, $formAccessorSecondCall);

        $this->assertSame($formAccessor->getForm(), $formAccessorSecondCall->getForm());
    }
}
