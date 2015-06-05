<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Form\Extension\ProductFormExtension;
use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceCollectionType;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\UniqueProductPrices;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ProductFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceManager;

    /**
     * @var ProductPriceRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceRepository;

    /**
     * @var RoundingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $roundingService;

    /**
     * @var ProductFormExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->priceRepository =
            $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
                ->disableOriginalConstructor()
                ->getMock();

        $this->priceManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->priceManager->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BPricingBundle:ProductPrice')
            ->willReturn($this->priceRepository);

        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroB2BPricingBundle:ProductPrice')
            ->willReturn($this->priceManager);

        $this->roundingService = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Rounding\RoundingService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ProductFormExtension($registry, $this->roundingService);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(ProductType::NAME, $this->extension->getExtendedType());
    }

    public function testBuildForm()
    {
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'prices',
                ProductPriceCollectionType::NAME,
                [
                    'label' => 'orob2b.pricing.productprice.entity_plural_label',
                    'required' => false,
                    'mapped' => false,
                    'constraints' => [new UniqueProductPrices()]
                ]
            );
        $builder->expects($this->exactly(3))
            ->method('addEventListener');
        $builder->expects($this->at(1))
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, [$this->extension, 'onPostSetData']);
        $builder->expects($this->at(2))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SUBMIT, [$this->extension, 'onPreSubmit']);
        $builder->expects($this->at(3))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->extension, 'onPostSubmit'], 10);

        $this->extension->buildForm($builder, []);
    }

    /**
     * @param Product|null $product
     * @dataProvider onPostSetDataDataProvider
     */
    public function testOnPostSetData($product)
    {
        $event = $this->createEvent($product);

        if ($product && $product->getId()) {
            $prices = ['price1', 'price2'];

            $this->priceRepository->expects($this->once())
                ->method('getPricesByProduct')
                ->with($product)
                ->willReturn($prices);

            /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $pricesForm */
            $pricesForm = $event->getForm()->get('prices');
            $pricesForm->expects($this->once())
                ->method('setData')
                ->with($prices);
        } else {
            $this->priceRepository->expects($this->never())
                ->method('getPricesByProduct');
        }

        $this->extension->onPostSetData($event);
    }

    /**
     * @return array
     */
    public function onPostSetDataDataProvider()
    {
        return [
            'no product'       => [null],
            'new product'      => [$this->createProduct()],
            'existing product' => [$this->createProduct(1)],
        ];
    }

    /**
     * @param array $sourceData
     * @param array $expectedData
     * @dataProvider onPreSubmitDataProvider
     */
    public function testOnPreSubmit(array $sourceData, array $expectedData)
    {
        $this->roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(
                function ($quantity, $precision) {
                    return round($quantity, $precision);
                }
            );

        $event = $this->createEvent($sourceData);
        $this->extension->onPreSubmit($event);
        $this->assertEquals($expectedData, $event->getData());
    }

    /**
     * @return array
     */
    public function onPreSubmitDataProvider()
    {
        return [
            'empty data' => [
                'sourceData' => [],
                'expectedData' => [],
            ],
            'no prices' => [
                'sourceData' => [
                    'unitPrecisions' => [
                        ['unit' => 'item', 'precision' => 0],
                        ['unit' => 'kg', 'precision' => 3],
                    ]
                ],
                'expectedData' => [
                    'unitPrecisions' => [
                        ['unit' => 'item', 'precision' => 0],
                        ['unit' => 'kg', 'precision' => 3],
                    ]
                ],
            ],
            'no unit precisions' => [
                'sourceData' => [
                    'prices' => [
                        ['quantity' => 12.345, 'unit' => 'kg']
                    ]
                ],
                'expectedData' => [
                    'prices' => [
                        ['quantity' => 12.345, 'unit' => 'kg']
                    ]
                ],
            ],
            'valid rounding data' => [
                'sourceData' => [
                    'unitPrecisions' => [
                        ['unit' => 'item', 'precision' => 0],
                        ['unit' => 'kg', 'precision' => 3],
                    ],
                    'prices' => [
                        ['quantity' => 12.3, 'unit' => 'item'],
                        ['quantity' => 12.3456, 'unit' => 'kg'],
                    ],
                ],
                'expectedData' => [
                    'unitPrecisions' => [
                        ['unit' => 'item', 'precision' => 0],
                        ['unit' => 'kg', 'precision' => 3],
                    ],
                    'prices' => [
                        ['quantity' => 12, 'unit' => 'item'],
                        ['quantity' => 12.346, 'unit' => 'kg'],
                    ],
                ],
            ]
        ];
    }

    public function testOnPostSubmitNoProduct()
    {
        $event = $this->createEvent(null);
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->never())
            ->method('isValid');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitInvalidForm()
    {
        $event = $this->createEvent($this->createProduct());
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $pricesForm */
        $pricesForm = $mainForm->get('prices');
        $pricesForm->expects($this->never())
            ->method('getData');

        $this->extension->onPostSubmit($event);
    }

    /**
     * @param mixed $data
     * @return FormEvent
     */
    protected function createEvent($data)
    {
        $pricesForm = $this->getMock('Symfony\Component\Form\FormInterface');

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $mainForm->expects($this->any())
            ->method('get')
            ->with('prices')
            ->willReturn($pricesForm);

        return new FormEvent($mainForm, $data);
    }

    /**
     * @param int|null $id
     * @return Product
     */
    protected function createProduct($id = null)
    {
        return $this->createEntity('OroB2B\Bundle\ProductBundle\Entity\Product', $id);
    }

    /**
     * @param int|null $id
     * @return ProductPrice
     */
    protected function createProductPrice($id = null)
    {
        return $this->createEntity('OroB2B\Bundle\PricingBundle\Entity\ProductPrice', $id);
    }

    /**
     * @param $class string
     * @param int|null $id
     * @return object
     */
    protected function createEntity($class, $id = null)
    {
        $entity = new $class();
        if ($id) {
            $reflection = new \ReflectionProperty($class, 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($entity, $id);
        }

        return $entity;
    }
}
