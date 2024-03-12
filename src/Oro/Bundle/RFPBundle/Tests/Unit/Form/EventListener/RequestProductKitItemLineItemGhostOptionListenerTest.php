<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\FormBundle\Tests\Unit\Stub\FormTypeStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Bundle\RFPBundle\Form\EventListener\RequestProductKitItemLineItemGhostOptionListener;
use Oro\Bundle\RFPBundle\Tests\Unit\Stub\RequestProductKitItemLineItemStub;
use Oro\Component\PhpUtils\ReflectionUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

class RequestProductKitItemLineItemGhostOptionListenerTest extends TestCase
{
    private RequestProductKitItemLineItemGhostOptionListener $listener;

    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->listener = new RequestProductKitItemLineItemGhostOptionListener();
        $this->listener->setGhostOptionClass(ProductStub::class);

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addType(new FormTypeStub(['class'], ChoiceType::class))
            ->getFormFactory();
    }

    public function testOnPreSetDataWhenNoData(): void
    {
        $product1 = (new ProductStub())->setId(42)->setDefaultName('Product42');
        $product2 = (new ProductStub())->setId(43)->setDefaultName('Product43');

        $formBuilder = $this->formFactory->createBuilder()
            ->add('product', FormTypeStub::class, ['choices' => [$product1, $product2]])
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertEquals([$product1, $product2], $form->get('product')->getConfig()->getOption('choices'));
    }

    public function testOnPreSetDataWhenKitItemLineItemIsNew(): void
    {
        $product1 = (new ProductStub())->setId(42)->setDefaultName('Product42');
        $product2 = (new ProductStub())->setId(43)->setDefaultName('Product43');

        $formBuilder = $this->formFactory->createBuilder(FormType::class, new RequestProductKitItemLineItem())
            ->add('product', FormTypeStub::class, ['choices' => [$product1, $product2]])
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertEquals([$product1, $product2], $form->get('product')->getConfig()->getOption('choices'));
    }

    public function testOnPreSetDataWhenProductPresent(): void
    {
        $product1 = (new ProductStub())->setId(42)->setDefaultName('Product42');
        $product2 = (new ProductStub())->setId(43)->setDefaultName('Product43');

        $kitItemLineItem = (new RequestProductKitItemLineItemStub(42))
            ->setProduct($product1);

        $formBuilder = $this->formFactory->createBuilder(FormType::class, $kitItemLineItem)
            ->add('product', FormTypeStub::class, ['choices' => [$product1, $product2]])
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertEquals([$product1, $product2], $form->get('product')->getConfig()->getOption('choices'));
    }

    public function testOnPreSetDataWhenProductNotPresent(): void
    {
        $product1 = (new ProductStub())->setId(42)->setDefaultName('Product42');
        $product2 = (new ProductStub())->setId(43)->setDefaultName('Product43');
        $product3 = (new ProductStub())->setId(44)->setDefaultName('Product44');

        $kitItemLineItem = (new RequestProductKitItemLineItemStub(42))
            ->setProduct($product3);

        $formBuilder = $this->formFactory->createBuilder(FormType::class, $kitItemLineItem)
            ->add('product', FormTypeStub::class, ['choices' => [$product1, $product2]])
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        $expectedChoices = [$product3, $product1, $product2];
        self::assertEquals($expectedChoices, $form->get('product')->getConfig()->getOption('choices'));
        self::assertEquals($product3, $form->get('product')->getConfig()->getOption('data'));

        $form->submit(['product' => array_search($product3, $expectedChoices, true)]);

        self::assertTrue($form->isValid());
        self::assertSame($product3, $form->getData()?->getProduct());

        $formView = $form->createView();
        self::assertEquals(
            ['data-ghost-option' => true, 'class' => 'ghost-option'],
            $formView['product']->vars['choices'][0]->attr
        );
        self::assertEquals([], $formView['product']->vars['choices'][1]->attr);
        self::assertEquals([], $formView['product']->vars['choices'][2]->attr);
    }

    public function testOnPreSetDataWhenProductEntityIsNotSetButHasProductSku(): void
    {
        $product1 = (new ProductStub())->setId(42)->setDefaultName('Product42');
        $product2 = (new ProductStub())->setId(43)->setDefaultName('Product43');

        $kitItemLineItem = (new RequestProductKitItemLineItemStub(42))
            ->setProductSku('GP1')
            ->setProductName('Ghost Product');

        $formBuilder = $this->formFactory->createBuilder(FormType::class, $kitItemLineItem)
            ->add('product', FormTypeStub::class, ['choices' => [$product1, $product2]])
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        $ghostProduct = (new ProductStub())
            ->setSku($kitItemLineItem->getProductSku())
            ->setDefaultName($kitItemLineItem->getProductName());

        ReflectionUtil::getProperty(new \ReflectionClass(Product::class), 'id')
            ?->setValue($ghostProduct, PHP_INT_MIN);

        $expectedChoices = [$ghostProduct, $product1, $product2];
        self::assertEquals(
            $expectedChoices,
            $form->get('product')->getConfig()->getOption('choices')
        );
        self::assertEquals($ghostProduct, $form->get('product')->getConfig()->getOption('data'));

        $form->submit(['product' => array_search($ghostProduct, $expectedChoices, true)]);

        self::assertTrue($form->isValid());
        self::assertEquals('GP1', $form->getData()?->getProductSku());
        self::assertEquals('Ghost Product', $form->getData()?->getProductName());

        $formView = $form->createView();
        self::assertEquals(
            ['data-ghost-option' => true, 'class' => 'ghost-option'],
            $formView['product']->vars['choices'][0]->attr
        );
        self::assertEquals([], $formView['product']->vars['choices'][1]->attr);
        self::assertEquals([], $formView['product']->vars['choices'][2]->attr);
    }

    public function testOnPreSetDataWhenProductEntityIsNotSetAndNoProductSku(): void
    {
        $product1 = (new ProductStub())->setId(42)->setDefaultName('Product42');
        $product2 = (new ProductStub())->setId(43)->setDefaultName('Product43');

        $kitItemLineItem = new RequestProductKitItemLineItemStub(42);

        $formBuilder = $this->formFactory->createBuilder(FormType::class, $kitItemLineItem)
            ->add('product', FormTypeStub::class, ['choices' => [$product1, $product2]])
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertEquals([$product1, $product2], $form->get('product')->getConfig()->getOption('choices'));
    }
}
