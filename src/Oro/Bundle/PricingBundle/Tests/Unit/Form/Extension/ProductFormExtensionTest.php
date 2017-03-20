<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Form\Extension\ProductFormExtension;
use Oro\Bundle\PricingBundle\Form\Type\ProductPriceCollectionType;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniqueProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

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
     * @var ProductFormExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->priceRepository = $this
            ->getMockBuilder(ProductPriceRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceManager = $this->createMock(ObjectManager::class);
        $this->priceManager
            ->expects(static::any())
            ->method('getRepository')
            ->with('OroPricingBundle:ProductPrice')
            ->willReturn($this->priceRepository);

        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects(static::any())
            ->method('getManagerForClass')
            ->with('OroPricingBundle:ProductPrice')
            ->willReturn($this->priceManager);

        $this->extension = new ProductFormExtension($registry);
    }

    public function testGetExtendedType()
    {
        static::assertEquals(ProductType::NAME, $this->extension->getExtendedType());
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder
            ->expects(static::once())
            ->method('add')
            ->with(
                'prices',
                ProductPriceCollectionType::NAME,
                [
                    'label' => 'oro.pricing.productprice.entity_plural_label',
                    'required' => false,
                    'mapped' => false,
                    'constraints' =>
                        [
                            new UniqueProductPrices()
                        ],
                    'options' =>
                        [
                            'product' => null,
                        ],
                ]
            );

        $builder
            ->expects(static::exactly(3))
            ->method('addEventListener');

        $builder
            ->expects(static::at(2))
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, [$this->extension, 'onPostSetData']);
        $builder
            ->expects(static::at(3))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SUBMIT, [$this->extension, 'onPreSubmit'], 10);
        $builder
            ->expects(static::at(4))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->extension, 'onPostSubmit'], 10);

        $this->extension->buildForm($builder, []);
    }

    /**
     * @param Product|null $product
     *
     * @dataProvider onPostSetDataDataProvider
     */
    public function testOnPostSetData($product)
    {
        $event = $this->createEvent($product);

        if ($product && $product->getId()) {
            $prices = ['price1', 'price2'];

            $this->priceRepository
                ->expects(static::once())
                ->method('getPricesByProduct')
                ->with($product)
                ->willReturn($prices);

            /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $pricesForm */
            $pricesForm = $event
                ->getForm()
                ->get('prices');
            $pricesForm
                ->expects(static::once())
                ->method('setData')
                ->with($prices);
        } else {
            $this->priceRepository
                ->expects(static::never())
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
            'no product' => [null],
            'new product' => [$this->createProduct()],
            'existing product' => [$this->createProduct(1)]
        ];
    }

    public function testOnPostSubmitNoProduct()
    {
        $event = $this->createEvent(null);
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm
            ->expects(static::never())
            ->method('isValid');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitInvalidForm()
    {
        $event = $this->createEvent($this->createProduct());
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm
            ->expects(static::once())
            ->method('isValid')
            ->willReturn(false);

        $priceOne = $this->createProductPrice(1);
        $priceTwo = $this->createProductPrice(2);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $pricesForm */
        $pricesForm = $mainForm->get('prices');
        $pricesForm
            ->expects(static::once())
            ->method('getData')
            ->willReturn(
                [
                    $priceOne,
                    $priceTwo
                ]
            );

        $this->priceManager
            ->expects(static::never())
            ->method('persist');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitNewProduct()
    {
        $product = $this->createProduct();
        $event = $this->createEvent($product);

        $priceOne = $this->createProductPrice(1);
        $priceTwo = $this->createProductPrice(2);

        $this->assertPriceAdd($event, [$priceOne, $priceTwo]);
        $this->priceRepository->expects(static::never())
            ->method('getPricesByProduct');

        $this->extension->onPostSubmit($event);

        static::assertEquals($product, $priceOne->getProduct());
        static::assertEquals($product, $priceTwo->getProduct());
    }

    public function testOnPostSubmitExistingProduct()
    {
        $product = $this->createProduct(1);
        $event = $this->createEvent($product);

        $priceOne = $this->createProductPrice(1);
        $priceTwo = $this->createProductPrice(2);
        $removedPrice = $this->createProductPrice(3);

        $this->assertPriceAdd($event, [$priceOne, $priceTwo]);
        $this->priceRepository
            ->expects(static::once())
            ->method('getPricesByProduct')
            ->will(static::returnValue([$removedPrice]));

        $this->priceManager
            ->expects(static::once())
            ->method('remove')
            ->with($removedPrice);

        $this->extension->onPostSubmit($event);

        static::assertEquals($product, $priceOne->getProduct());
        static::assertEquals($product, $priceTwo->getProduct());
    }

    /**
     * @param mixed $data
     *
     * @return FormEvent
     */
    protected function createEvent($data)
    {
        $pricesForm = $this->createMock('Symfony\Component\Form\FormInterface');

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $this->createMock('Symfony\Component\Form\FormInterface');
        $mainForm->expects(static::any())
            ->method('get')
            ->with('prices')
            ->willReturn($pricesForm);

        return new FormEvent($mainForm, $data);
    }

    /**
     * @param int|null $id
     *
     * @return Product
     */
    protected function createProduct($id = null)
    {
        return $this->createEntity('Oro\Bundle\ProductBundle\Entity\Product', $id);
    }

    /**
     * @param int|null $id
     *
     * @return ProductPrice
     */
    protected function createProductPrice($id = null)
    {
        return $this->createEntity('Oro\Bundle\PricingBundle\Entity\ProductPrice', $id);
    }

    /**
     * @param string   $class
     * @param int|null $id
     *
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

    /**
     * @param FormEvent $event
     * @param array     $prices
     */
    protected function assertPriceAdd(FormEvent $event, array $prices)
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm
            ->expects(static::once())
            ->method('isValid')
            ->willReturn(true);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $pricesForm */
        $pricesForm = $mainForm->get('prices');
        $pricesForm
            ->expects(static::once())
            ->method('getData')
            ->will(static::returnValue($prices));

        $this->priceManager
            ->expects(static::exactly(count($prices)))
            ->method('persist');
    }
}
