<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendCustomerTypedAddressType;
use Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendCustomerAddressFormProvider;

class FrontendCustomerAddressFormProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var FrontendCustomerAddressFormProvider */
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
        $this->router = $this->createMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $this->provider = new FrontendCustomerAddressFormProvider($this->mockFormFactory, $this->router);
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
        /** @var CustomerAddress|\PHPUnit_Framework_MockObject_MockObject $mockCustomerUserAddress */
        $mockCustomerUserAddress = $this->getMockBuilder(CustomerAddress::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockCustomerUserAddress->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        /** @var Customer|\PHPUnit_Framework_MockObject_MockObject $mockCustomerUser */
        $mockCustomerUser = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockCustomerUser->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $mockFormView = $this->createMock(FormView::class);

        $mockForm = $this->getMockBuilder(FormInterface::class)->getMock();
        $mockForm->expects($this->once())
            ->method('createView')
            ->willReturn($mockFormView);

        $this->mockFormFactory->expects($this->once())
            ->method('create')
            ->with(FrontendCustomerTypedAddressType::NAME, $mockCustomerUserAddress)
            ->willReturn($mockForm);

        $form = $this->provider->getAddressFormView($mockCustomerUserAddress, $mockCustomerUser);

        $this->assertInstanceOf(FormView::class, $form);

        $formSecondCall = $this->provider->getAddressFormView($mockCustomerUserAddress, $mockCustomerUser);
        $this->assertSame($form, $formSecondCall);
    }
}
