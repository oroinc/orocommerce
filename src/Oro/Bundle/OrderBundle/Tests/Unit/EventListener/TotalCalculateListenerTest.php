<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\TotalCalculateListener;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;

class TotalCalculateListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var FormRegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formRegistry;

    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendHelper;

    /** @var TotalCalculateListener */
    private $listener;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactory::class);
        $this->formRegistry = $this->createMock(FormRegistryInterface::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);

        $this->listener = new TotalCalculateListener(
            $this->formFactory,
            $this->formRegistry,
            $this->frontendHelper
        );
    }

    public function testOnBeforeTotalCalculateWhenEntityIsNotOrder()
    {
        $this->frontendHelper->expects($this->never())
            ->method('isFrontendRequest');
        $this->formFactory->expects($this->never())
            ->method('create');

        $event = new TotalCalculateBeforeEvent(new \stdClass(), $this->getRequest());
        $this->listener->onBeforeTotalCalculate($event);
    }

    public function testOnBeforeTotalCalculateForFrontend()
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);
        $this->formFactory->expects($this->never())
            ->method('create');

        $event = new TotalCalculateBeforeEvent(new Order(), $this->getRequest());
        $this->listener->onBeforeTotalCalculate($event);
    }

    public function testOnBeforeTotalCalculateWhenRequestNotContainsData()
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);
        $this->formFactory->expects($this->never())
            ->method('create');

        $this->configureFormRegistry(OrderType::class, OrderType::NAME);

        $event = new TotalCalculateBeforeEvent(new Order(), $this->getRequest());
        $this->listener->onBeforeTotalCalculate($event);
    }

    public function testOnBeforeTotalCalculate()
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $entity = new Order();
        $formData = ['field' => 'value'];
        $request = $this->getRequest([OrderType::NAME => ['some data'], 'formName' => $formData]);
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getName')
            ->willReturn('formName');
        $form->expects($this->once())
            ->method('submit')
            ->with($formData);
        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(OrderType::class, $entity)
            ->willReturn($form);

        $this->configureFormRegistry(OrderType::class, OrderType::NAME);

        $event = new TotalCalculateBeforeEvent($entity, $request);
        $this->listener->onBeforeTotalCalculate($event);
    }

    /**
     * @param string $className
     * @param string $formName
     */
    private function configureFormRegistry($className, $formName)
    {
        $formType = $this->createMock(FormTypeInterface::class);
        $formType ->expects($this->any())
            ->method('getBlockPrefix')
            ->willReturn($formName);

        $this->formRegistry->expects($this->any())
            ->method('getType')
            ->with($className)
            ->willReturn($formType);
    }

    /**
     * @param array $postData
     * @return Request
     */
    private function getRequest(array $postData = [])
    {
        return new Request([], $postData);
    }
}
