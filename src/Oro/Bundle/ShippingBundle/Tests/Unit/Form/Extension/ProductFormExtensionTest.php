<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Form\Extension\ProductFormExtension;
use Oro\Bundle\ShippingBundle\Form\Type\ProductShippingOptionsCollectionType;
use Oro\Bundle\ShippingBundle\Validator\Constraints\UniqueProductUnitShippingOptions;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ProductFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repo;

    /** @var ProductFormExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(ObjectRepository::class);
        $this->manager = $this->createMock(ObjectManager::class);
        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with('OroShippingBundle:ProductShippingOptions')
            ->willReturn($this->repo);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroShippingBundle:ProductShippingOptions')
            ->willReturn($this->manager);

        $this->extension = new ProductFormExtension($doctrine);
    }

    /**
     * @dataProvider formDataProvider
     */
    public function testBuildForm(?Product $product)
    {
        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('add')
            ->with(
                ProductFormExtension::FORM_ELEMENT_NAME,
                ProductShippingOptionsCollectionType::class,
                [
                    'label' => 'oro.shipping.product_shipping_options.entity_plural_label',
                    'required' => false,
                    'mapped' => false,
                    'constraints' => [new UniqueProductUnitShippingOptions()],
                    'entry_options' => [
                        'product' => $product,
                    ],
                ]
            );

        $builder->expects($this->exactly(3))
            ->method('addEventListener')
            ->withConsecutive(
                [FormEvents::POST_SET_DATA, [$this->extension, 'onPostSetData']],
                [FormEvents::PRE_SUBMIT, [$this->extension, 'onPreSubmit']],
                [FormEvents::POST_SUBMIT, [$this->extension, 'onPostSubmit'], 10]
            );

        $builder->expects($this->once())
            ->method('getData')
            ->willReturn($product);

        $this->extension->buildForm($builder, []);
    }

    public function testGetExtendedTypes()
    {
        $this->assertEquals([ProductType::class], ProductFormExtension::getExtendedTypes());
    }

    /**
     * @dataProvider formDataProvider
     */
    public function testOnPostSetData(?Product $product)
    {
        $event = $this->createEvent($product);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $event->getForm()->get(ProductFormExtension::FORM_ELEMENT_NAME);

        $form->expects($product ? $this->once() : $this->never())
            ->method('setData');

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSubmitEmptyProduct()
    {
        $event = $this->createEvent();
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $event->getForm()->get(ProductFormExtension::FORM_ELEMENT_NAME);

        $form->expects($this->never())
            ->method('getData');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitValidData()
    {
        $product = $this->getProduct(1);

        $event = $this->createEvent($this->getProduct(1));
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $event->getForm()->get(ProductFormExtension::FORM_ELEMENT_NAME);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $removedOption = $this->getProductShippingOptions(42);

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['product' => $product], ['productUnit' => 'ASC'])
            ->willReturn([$removedOption]);

        $form->expects($this->once())
            ->method('getData')
            ->willReturn([$this->getProductShippingOptions(1), $this->getProductShippingOptions()]);

        $this->manager->expects($this->exactly(2))
            ->method('persist');
        $this->manager->expects($this->once())
            ->method('remove')
            ->with($removedOption);

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitInvalidData()
    {
        $event = $this->createEvent($this->getProduct(1));
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $event->getForm()->get(ProductFormExtension::FORM_ELEMENT_NAME);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $form->expects($this->once())
            ->method('getData')
            ->willReturn([$this->getProductShippingOptions(1)]);

        $this->manager->expects($this->never())
            ->method('persist');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPreSubmit()
    {
        $product = $this->getProduct(1);

        $event = $this->createEvent(
            [
                ProductFormExtension::FORM_ELEMENT_NAME => [
                    5 => ['productUnit' => 'test2'],
                    10 => ['productUnit' => 'test1']
                ]
            ]
        );
        $event->expects($this->once())
            ->method('setData')
            ->with(
                [
                    ProductFormExtension::FORM_ELEMENT_NAME => [
                        0 => ['productUnit' => 'test1'],
                        5 => ['productUnit' => 'test2']
                    ]
                ]
            );

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $event->getForm();
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($product);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $childForm */
        $childForm = $form->get(ProductFormExtension::FORM_ELEMENT_NAME);
        $childForm->expects($this->once())
            ->method('getData')
            ->willReturn([$this->getProductShippingOptions(42, $this->getProductUnit('test1'))]);

        $this->extension->onPreSubmit($event);
    }

    public function testOnPreSubmitWithoutProduct()
    {
        $product = $this->getProduct();

        $event = $this->createEvent();
        $event->expects($this->never())
            ->method('setData');

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $event->getForm();
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($product);

        $this->extension->onPreSubmit($event);
    }

    /**
     * @param mixed|null $data
     *
     * @return FormEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createEvent($data = null): FormEvent
    {
        $form = $this->createMock(FormInterface::class);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
        $mainForm = $this->createMock(FormInterface::class);
        $mainForm->expects($this->any())
            ->method('get')
            ->with(ProductFormExtension::FORM_ELEMENT_NAME)
            ->willReturn($form);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->any())
            ->method('getForm')
            ->willReturn($mainForm);
        $event->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        return $event;
    }

    public function formDataProvider(): array
    {
        $productMock = $this->getProduct(1);

        return [
            'no product' => ['product' => null],
            'with product' => ['product' => $productMock],
        ];
    }

    private function getProduct(int $id = null): Product
    {
        $product = new Product();
        if (null !== $id) {
            ReflectionUtil::setId($product, $id);
        }

        return $product;
    }

    private function getProductUnit(string $code): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }

    private function getProductShippingOptions(int $id = null, ProductUnit $productUnit = null): ProductShippingOptions
    {
        $productShippingOptions = new ProductShippingOptions();
        if (null !== $id) {
            ReflectionUtil::setId($productShippingOptions, $id);
        }
        if (null !== $productUnit) {
            $productShippingOptions->setProductUnit($productUnit);
        }

        return $productShippingOptions;
    }
}
