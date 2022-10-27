<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CustomerBundle\Entity\AbstractDefaultTypedAddress;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\OrderAddressEventListener;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Twig\Environment;

class OrderAddressEventListenerTest extends \PHPUnit\Framework\TestCase
{
    protected OrderAddressEventListener $listener;
    protected Environment|MockObject $twig;
    protected FormFactoryInterface|MockObject $formFactory;
    protected OrderAddressManager|MockObject $addressManager;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->addressManager = $this->createMock(OrderAddressManager::class);

        $this->listener = new OrderAddressEventListener($this->twig, $this->formFactory);
        $this->listener->setAddressManager($this->addressManager);
    }

    public function testOnOrderEvent(): void
    {
        $billingAddressFieldName = sprintf('%sAddress', AddressType::TYPE_BILLING);
        $shippingAddressFieldName = sprintf('%sAddress', AddressType::TYPE_SHIPPING);

        $order = $this->createMock(Order::class);
        $formConfig = $this->configureFormConfig();

        $oldForm = $this->createMock(Form::class);
        $oldForm->expects(self::any())->method('getName')->willReturn('order');
        $oldForm->expects(self::exactly(2))
            ->method('has')
            ->withConsecutive(
                [$this->equalTo($billingAddressFieldName)],
                [$this->equalTo($shippingAddressFieldName)]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );
        $this->assertOldBillingAddressFieldCalls($oldForm, $billingAddressFieldName, $formConfig);

        $newBillingAddressField = $this->createMock(FormInterface::class);
        $this->assertAddressSelectorRendering($newBillingAddressField, null);

        $newForm = $this->createMock(Form::class);
        $this->assertNewFormSubmit($newForm, $billingAddressFieldName, $newBillingAddressField);
        $this->assertDefaultAddressSet($order, $newBillingAddressField);

        $event = new OrderEvent($oldForm, $order, ['order' => []]);
        $this->listener->onOrderEvent($event);

        $eventData = $event->getData()->getArrayCopy();

        self::assertArrayHasKey($billingAddressFieldName, $eventData);
        self::assertEquals('view1', $eventData[$billingAddressFieldName]);
    }

    public function testOnOrderEventWithPassedAddress(): void
    {
        $billingAddressFieldName = sprintf('%sAddress', AddressType::TYPE_BILLING);
        $shippingAddressFieldName = sprintf('%sAddress', AddressType::TYPE_SHIPPING);

        $order = $this->createMock(Order::class);
        $formConfig = $this->configureFormConfig();

        $oldForm = $this->createMock(Form::class);
        $oldForm->expects(self::any())->method('getName')->willReturn('order');
        $oldForm->expects(self::exactly(2))
            ->method('has')
            ->withConsecutive(
                [$this->equalTo($billingAddressFieldName)],
                [$this->equalTo($shippingAddressFieldName)]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );
        $this->assertOldBillingAddressFieldCalls($oldForm, $billingAddressFieldName, $formConfig);

        $newBillingAddressField = $this->createMock(FormInterface::class);
        $this->assertAddressSelectorRendering($newBillingAddressField, new OrderAddress());

        $newForm = $this->createMock(Form::class);
        $this->assertNewFormSubmit($newForm, $billingAddressFieldName, $newBillingAddressField);
        $this->addressManager->expects(self::never())
            ->method($this->anything());
        $order->expects($this->never())
            ->method('setBillingAddress');

        $event = new OrderEvent($oldForm, $order, ['order' => []]);
        $this->listener->onOrderEvent($event);

        $eventData = $event->getData()->getArrayCopy();

        self::assertArrayHasKey($billingAddressFieldName, $eventData);
        self::assertEquals('view1', $eventData[$billingAddressFieldName]);
    }

    public function testDoNothingIfNoSubmission(): void
    {
        $event = $this->createMock(OrderEvent::class);
        $event->expects(self::never())
            ->method('getForm');

        $this->listener->onOrderEvent($event);
    }

    /**
     * @param Order|MockObject $order
     * @param MockObject|FormInterface $newBillingAddressField
     * @return void
     */
    private function assertDefaultAddressSet(
        Order $order,
        FormInterface $newBillingAddressField
    ): void {
        $defaultAddress = $this->createMock(AbstractDefaultTypedAddress::class);
        $addressesCollection = $this->createMock(TypedOrderAddressCollection::class);
        $addressesCollection->expects(self::once())
            ->method('getDefaultAddress')
            ->willReturn($defaultAddress);

        $billingAddress = new OrderAddress();
        $this->addressManager->expects(self::once())
            ->method('updateFromAbstract')
            ->with($defaultAddress)
            ->willReturn($billingAddress);
        $order->expects(self::once())
            ->method('getBillingAddress')
            ->willReturn(null);
        $order->expects(self::once())
            ->method('setBillingAddress')
            ->with($billingAddress);

        $customerAddressFieldConfig = $this->createMock(FormConfigInterface::class);
        $customerAddressFieldConfig->expects(self::once())
            ->method('getOption')
            ->with('address_collection')
            ->willReturn($addressesCollection);
        $customerAddressField = $this->createMock(FormInterface::class);
        $customerAddressField->expects(self::once())
            ->method('getConfig')
            ->willReturn($customerAddressFieldConfig);
        $newBillingAddressField->expects(self::once())
            ->method('get')
            ->with('customerAddress')
            ->willReturn($customerAddressField);
    }

    private function assertOldBillingAddressFieldCalls(
        MockObject $form,
        string $addressFieldName,
        MockObject $formConfig
    ): void {
        $addressField = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with($addressFieldName)
            ->willReturn($addressField);

        $addressField->expects(self::any())->method('getConfig')->willReturn($formConfig);
        $addressField->expects(self::any())->method('getName')->willReturn('name');
        $addressField->expects(self::any())->method('getData')->willReturn([]);
    }

    private function configureFormConfig(): FormConfigInterface|MockObject
    {
        $type = $this->createMock(ResolvedFormTypeInterface::class);
        $type->expects(self::once())->method('getInnerType')->willReturn(new FormType());

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects(self::once())->method('getOptions')->willReturn([]);
        $formConfig->expects(self::once())->method('getType')->willReturn($type);

        return $formConfig;
    }

    private function assertAddressSelectorRendering(MockObject $addressField, mixed $value): void
    {
        $addressFieldView = $this->createMock(FormView::class);
        $addressFieldView->vars['value'] = $value;
        $this->twig->expects(self::once())
            ->method('render')
            ->with('@OroOrder/Form/customerAddressSelector.html.twig', ['form' => $addressFieldView])
            ->willReturn('view1');

        $addressField
            ->expects(self::once())
            ->method('createView')
            ->willReturn($addressFieldView);
    }

    private function assertNewFormSubmit(
        MockObject $form,
        string $addressFieldName,
        MockObject $addressField
    ): void {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())->method('add')->with('billingAddress', FormType::class, $this->isType('array'))
            ->willReturnSelf();
        $builder->expects(self::once())->method('getForm')->willReturn($form);
        $this->formFactory->expects(self::once())->method('createNamedBuilder')->willReturn($builder);
        $form->expects(self::once())
            ->method('get')
            ->with($addressFieldName)
            ->willReturn($addressField);
        $form->expects(self::once())->method('submit')->with($this->isType('array'));
    }
}
