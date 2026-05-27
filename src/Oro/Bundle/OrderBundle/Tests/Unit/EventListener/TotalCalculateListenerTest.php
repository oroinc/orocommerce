<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\TotalCalculateListener;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\HttpFoundation\Request;

final class TotalCalculateListenerTest extends TestCase
{
    private FormFactory&MockObject $formFactory;

    private FormRegistryInterface&MockObject $formRegistry;

    private FrontendHelper&MockObject $frontendHelper;

    private TotalCalculateListener $listener;

    #[\Override]
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

    public function testOnBeforeTotalCalculateWhenEntityIsNotOrder(): void
    {
        $this->frontendHelper
            ->expects(self::never())
            ->method('isFrontendRequest');

        $this->formFactory
            ->expects(self::never())
            ->method('create');

        $event = new TotalCalculateBeforeEvent(new \stdClass(), new Request());
        $this->listener->onBeforeTotalCalculate($event);
    }

    public function testOnBeforeTotalCalculateForFrontend(): void
    {
        $this->frontendHelper
            ->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->formFactory
            ->expects(self::never())
            ->method('create');

        $event = new TotalCalculateBeforeEvent(new Order(), new Request());
        $this->listener->onBeforeTotalCalculate($event);
    }

    public function testOnBeforeTotalCalculateWhenRequestDoesNotContainOrderTypeData(): void
    {
        $this->frontendHelper
            ->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->formFactory
            ->expects(self::never())
            ->method('create');

        $this->configureFormRegistry(OrderType::class, 'order');

        $event = new TotalCalculateBeforeEvent(new Order(), new Request());
        $this->listener->onBeforeTotalCalculate($event);
    }

    public function testOnBeforeTotalCalculateSubmitsFormWhenPayloadExists(): void
    {
        $this->frontendHelper
            ->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $entity = new Order();
        $formPrefix = 'order';
        $formData = ['field' => 'value'];
        $request = new Request([], [$formPrefix => ['irrelevant' => 'value'], 'formName' => $formData]);

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('getName')
            ->willReturn('formName');

        $form
            ->expects(self::once())
            ->method('submit')
            ->with($formData);

        $this->formFactory
            ->expects(self::once())
            ->method('create')
            ->with(OrderType::class, $entity, ['draft_session_sync' => true])
            ->willReturn($form);

        $this->configureFormRegistry(OrderType::class, $formPrefix);

        $event = new TotalCalculateBeforeEvent($entity, $request);
        $this->listener->onBeforeTotalCalculate($event);
    }

    private function configureFormRegistry(string $className, string $formName): void
    {
        $formType = $this->createMock(ResolvedFormTypeInterface::class);
        $formType
            ->expects(self::once())
            ->method('getBlockPrefix')
            ->willReturn($formName);

        $this->formRegistry
            ->expects(self::once())
            ->method('getType')
            ->with($className)
            ->willReturn($formType);
    }
}
