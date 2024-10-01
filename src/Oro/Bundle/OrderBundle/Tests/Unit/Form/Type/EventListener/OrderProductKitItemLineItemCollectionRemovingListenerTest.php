<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\EventListener;

use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\OrderProductKitItemLineItemCollectionRemovingListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

class OrderProductKitItemLineItemCollectionRemovingListenerTest extends TestCase
{
    private FormFactoryInterface $formFactory;

    private OrderProductKitItemLineItemCollectionRemovingListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new OrderProductKitItemLineItemCollectionRemovingListener();

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->getFormFactory();
    }

    public function testOnSubmitEmptyCollection(): void
    {
        $formBuilder = $this->formFactory->createBuilder(FormType::class, ['kitItemLineItems' => null])
            ->add('kitItemLineItems', FormType::class);

        $formBuilder
            ->get('kitItemLineItems')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        $form->submit(['kitItemLineItems' => null]);

        self::assertSame(['kitItemLineItems' => []], $form->getData());
    }

    public function testOnSubmitWhenContainsElementsNotRepresentedInForm(): void
    {
        $kitItemLineItem1 = new OrderProductKitItemLineItem();
        $formBuilder = $this->formFactory->createBuilder()
            ->add('kitItemLineItems', FormType::class);

        $formBuilder
            ->get('kitItemLineItems')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        $form->submit(['kitItemLineItems' => [1 => $kitItemLineItem1]]);

        self::assertSame(['kitItemLineItems' => []], $form->getData());
    }

    public function testOnSubmitWhenContainsKitItemWithoutId(): void
    {
        $kitItemLineItem1 = new OrderProductKitItemLineItem();
        $formBuilder = $this->formFactory->createBuilder()
            ->add('kitItemLineItems', FormType::class);

        $formBuilder
            ->get('kitItemLineItems')
            ->addEventSubscriber($this->listener)
            ->add('1', FormType::class, ['compound' => false]);

        $form = $formBuilder->getForm();

        $form->submit(['kitItemLineItems' => ['1' => $kitItemLineItem1]]);

        self::assertSame(['kitItemLineItems' => []], $form->getData());
    }

    public function testOnSubmitWhenContainsRequiredKitItemWithId(): void
    {
        $kitItemLineItem1 = (new OrderProductKitItemLineItem())
            ->setKitItemId(1);
        $formBuilder = $this->formFactory->createBuilder()
            ->add('kitItemLineItems', FormType::class);

        $formBuilder
            ->get('kitItemLineItems')
            ->addEventSubscriber($this->listener)
            ->add('1', FormType::class, ['compound' => false]);

        $form = $formBuilder->getForm();

        $form->submit(['kitItemLineItems' => ['1' => $kitItemLineItem1]]);

        self::assertSame(['kitItemLineItems' => ['1' => $kitItemLineItem1]], $form->getData());
    }

    public function testOnSubmitWhenContainsOptionalKitItemWithProduct(): void
    {
        $kitItemLineItem1 = (new OrderProductKitItemLineItem())
            ->setKitItemId(1)
            ->setOptional(true)
            ->setProductId(42);
        $formBuilder = $this->formFactory->createBuilder()
            ->add('kitItemLineItems', FormType::class);

        $formBuilder
            ->get('kitItemLineItems')
            ->addEventSubscriber($this->listener)
            ->add('1', FormType::class, ['compound' => false]);

        $form = $formBuilder->getForm();

        $form->submit(['kitItemLineItems' => ['1' => $kitItemLineItem1]]);

        self::assertSame(['kitItemLineItems' => ['1' => $kitItemLineItem1]], $form->getData());
    }

    public function testOnSubmitWhenContainsOptionalKitItemWithoutProduct(): void
    {
        $kitItemLineItem1 = (new OrderProductKitItemLineItem())
            ->setKitItemId(1)
            ->setOptional(true);
        $formBuilder = $this->formFactory->createBuilder()
            ->add('kitItemLineItems', FormType::class);

        $formBuilder
            ->get('kitItemLineItems')
            ->addEventSubscriber($this->listener)
            ->add('1', FormType::class, ['compound' => false]);

        $form = $formBuilder->getForm();

        $form->submit(['kitItemLineItems' => ['1' => $kitItemLineItem1]]);

        self::assertSame(['kitItemLineItems' => []], $form->getData());
    }
}
