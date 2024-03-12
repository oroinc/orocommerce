<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Bundle\RFPBundle\Form\EventListener\RequestProductKitItemLineItemCollectionRemovingListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

class RequestProductKitItemLineItemCollectionRemovingListenerTest extends TestCase
{
    private FormFactoryInterface $formFactory;

    private RequestProductKitItemLineItemCollectionRemovingListener $listener;

    protected function setUp(): void
    {
        $this->listener = new RequestProductKitItemLineItemCollectionRemovingListener();

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
        $kitItemLineItem1 = new RequestProductKitItemLineItem();
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
        $kitItemLineItem1 = new RequestProductKitItemLineItem();
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
        $kitItemLineItem1 = (new RequestProductKitItemLineItem())
            ->setKitItemId(1);
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

    public function testOnSubmitWhenContainsOptionalKitItemWithProduct(): void
    {
        $kitItemLineItem1 = (new RequestProductKitItemLineItem())
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

        self::assertSame(['kitItemLineItems' => []], $form->getData());
    }

    /**
     * @dataProvider getOnSubmitWhenContainsKitItemAndProductDataProvider
     * @param bool $isOptional
     *
     * @return void
     */
    public function testOnSubmitWhenContainsKitItemAndProduct(bool $isOptional): void
    {
        $kitItem = new ProductKitItemStub(1);
        $kitItemLineItem1 = (new RequestProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setOptional($isOptional)
            ->setProduct((new ProductStub())->setId(1));
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

    public function getOnSubmitWhenContainsKitItemAndProductDataProvider(): array
    {
        return [
            [false],
            [true],
        ];
    }

    public function testOnSubmitWhenContainsOptionalKitItemWithoutProduct(): void
    {
        $kitItemLineItem1 = (new RequestProductKitItemLineItem())
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
