<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Form\Extension\ProductFormExtension;
use Oro\Bundle\PricingBundle\Form\Type\ProductPriceCollectionType;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniqueProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var UnitOfWork */
    protected $uow;

    /** @var PriceManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $priceManager;

    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $shardManager;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var ProductPriceRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $priceRepository;

    /** @var ProductFormExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->priceRepository = $this->createMock(ProductPriceRepository::class);

        $this->em = $this->createMock(EntityManager::class);
        $this->uow = $this->createMock(UnitOfWork::class);

        $this->em->method('getUnitOfWork')->willReturn($this->uow);
        $this->em->method('getClassMetadata')->willReturn($this->createMock(ClassMetadata::class));
        $this->em->expects($this->any())
            ->method('getRepository')
            ->with('OroPricingBundle:ProductPrice')
            ->willReturn($this->priceRepository);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->createMock('Doctrine\Persistence\ManagerRegistry');
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroPricingBundle:ProductPrice')
            ->willReturn($this->em);
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->priceManager = $this->createMock(PriceManager::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->extension = new ProductFormExtension(
            $registry,
            $this->shardManager,
            $this->priceManager,
            $this->authorizationChecker
        );
    }

    public function testBuildFormFeatureDisabled()
    {
        /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject $featureChecker */
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->never())->method('add');

        $this->extension->setFeatureChecker($featureChecker);
        $this->extension->addFeature('feature1');
        $this->extension->buildForm($builder, []);
    }

    /**
     * @dataProvider badProductDataProvider
     */
    public function testOnPreSubmitBadProduct(Product $product = null)
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getData')
            ->willReturn($product);

        $this->priceRepository
            ->expects($this->never())
            ->method('getPricesByProduct');

        $event = new FormEvent($form, []);

        $this->extension->onPreSubmit($event);
    }

    /**
     * @return array
     */
    public function badProductDataProvider()
    {
        return [
            'no product' => [
                'product' => null,
            ],
            'new product' => [
                'product' => new Product(),
            ]
        ];
    }

    public function testOnPreSubmitAndNoPriceField()
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getData')
            ->willReturn($this->getEntity(Product::class, ['id' => 1]));

        $form
            ->expects($this->once())
            ->method('has')
            ->with('prices')
            ->willReturn(false);

        $this->priceRepository
            ->expects($this->never())
            ->method('getPricesByProduct');

        $event = new FormEvent($form, []);

        $this->extension->onPreSubmit($event);
    }

    public function testOnPreSubmitNoPrices()
    {
        $product = $this->getEntity(Product::class, ['id' => 1]);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getData')
            ->willReturn($product);

        $this->priceRepository
            ->expects($this->never())
            ->method('getPricesByProduct');

        $event = new FormEvent($form, ['title' => 'Title']);

        $this->extension->onPreSubmit($event);
    }

    /**
     * @dataProvider preSubmitPricesDataProvider
     */
    public function testOnPreSubmitWithPrices(array $existingPrices, array $submittedPrices, array $expectedPrices)
    {
        $product = $this->getEntity(Product::class, ['id' => 1]);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getData')
            ->willReturn($product);

        $form
            ->expects($this->once())
            ->method('has')
            ->with('prices')
            ->willReturn(true);

        $this->priceRepository
            ->expects($this->once())
            ->method('getPricesByProduct')
            ->with($this->shardManager, $product)
            ->willReturn($existingPrices);

        $event = new FormEvent($form, ['prices' => $submittedPrices]);
        $this->extension->onPreSubmit($event);

        $this->assertEquals($expectedPrices, $event->getData()['prices']);
    }

    /**
     * @return array
     */
    public function preSubmitPricesDataProvider()
    {
        $priceList1 = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);

        $price1 = $this->getEntity(ProductPrice::class, [
            'priceList' => $priceList1,
            'price' => Price::create(10, 'USD'),
            'unit' => $this->getEntity(ProductUnit::class, ['code' => 'kg']),
            'quantity' => 1
        ]);

        $price2 = $this->getEntity(ProductPrice::class, [
            'priceList' => $priceList2,
            'price' => Price::create(11, 'USD'),
            'unit' => $this->getEntity(ProductUnit::class, ['code' => 'kg']),
            'quantity' => 2
        ]);

        $price3 = $this->getEntity(ProductPrice::class, [
            'priceList' => $priceList2,
            'price' => Price::create(12, 'USD'),
            'unit' => $this->getEntity(ProductUnit::class, ['code' => 'kg']),
            'quantity' => 3
        ]);

        return [
            'if existing prices are missing in submitted prices they are not added to prices collection' => [
                'existingPrices' => [
                    0 => $price1,
                    1 => $price2
                ],
                'submittedPrices' => [
                    1 => [
                        'priceList' => $priceList2,
                        'price' => ['currency' => 'USD', 'value' => 11],
                        'quantity' => 2,
                        'unit' => 'kg'
                    ]
                ],
                'expectedPrices' => [
                    1 => [
                        'priceList' => $priceList2,
                        'price' => ['currency' => 'USD', 'value' => 11],
                        'quantity' => 2,
                        'unit' => 'kg'
                    ]
                ],
            ],
            'prices replaced by their unique attributes (when quantities were swapped)' => [
                'existingPrices' => [
                    0 => $price1,
                    1 => $price2,
                    2 => $price3
                ],
                'submittedPrices' => [
                    0 => [
                        'priceList' => $priceList1,
                        'price' => ['currency' => 'USD', 'value' => 11],
                        'quantity' => 1,
                        'unit' => 'kg'
                    ],
                    1 => [
                        'priceList' => $priceList2,
                        'price' => ['currency' => 'USD', 'value' => 12],
                        'quantity' => 3, // note that this quantity was swapped with next price quantity
                        'unit' => 'kg'
                    ],
                    2 => [
                        'priceList' => $priceList2,
                        'price' => ['currency' => 'USD', 'value' => 13],
                        'quantity' => 2, // note that this quantity was swapped with previous price quantity
                        'unit' => 'kg'
                    ]
                ],
                'expectedPrices' => [
                    0 => [
                        'priceList' => $priceList1,
                        'price' => ['currency' => 'USD', 'value' => 11],
                        'quantity' => 1,
                        'unit' => 'kg'
                    ],
                    1 => [
                        'priceList' => $priceList2,
                        'price' => ['currency' => 'USD', 'value' => 12],
                        'quantity' => 3, // note that this quantity was swapped with next price quantity
                        'unit' => 'kg'
                    ],
                    2 => [
                        'priceList' => $priceList2,
                        'price' => ['currency' => 'USD', 'value' => 13],
                        'quantity' => 2, // note that this quantity was swapped with previous price quantity
                        'unit' => 'kg'
                    ]
                ],
            ]
        ];
    }

    public function testGetExtendedTypes()
    {
        $this->assertEquals([ProductType::class], ProductFormExtension::getExtendedTypes());
    }

    public function testBuildForm()
    {
        /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject $featureChecker */
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock('Symfony\Component\Form\FormBuilderInterface');

        $builder
            ->expects(static::exactly(4))
            ->method('addEventListener');

        $builder
            ->expects(static::at(0))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA, [$this->extension, 'addFormOnPreSetData']);

        $builder
            ->expects(static::at(1))
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, [$this->extension, 'onPostSetData']);
        $builder
            ->expects(static::at(2))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SUBMIT, [$this->extension, 'onPreSubmit'], 10);
        $builder
            ->expects(static::at(3))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->extension, 'onPostSubmit'], 10);

        $this->extension->setFeatureChecker($featureChecker);
        $this->extension->addFeature('feature1');
        $this->extension->buildForm($builder, []);
    }

    public function testAddFormOnPreSetDataAndFieldPricesAlreadyAdded()
    {
        $product = $this->getEntity(Product::class, ['id' => 1]);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->never())
            ->method('add');

        $form
            ->expects($this->once())
            ->method('has')
            ->with('prices')
            ->willReturn(true);

        $this->authorizationChecker
            ->expects($this->never())
            ->method('isGranted');

        $formEvent = new FormEvent($form, $product);
        $this->extension->addFormOnPreSetData($formEvent);
    }

    public function testAddFormOnPreSetDataAndNewProduct()
    {
        $product = $this->getEntity(Product::class, ['id' => null]);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('add')
            ->with(
                'prices',
                ProductPriceCollectionType::class,
                [
                    'label' => 'oro.pricing.productprice.entity_plural_label',
                    'required' => false,
                    'mapped' => false,
                    'constraints' => [
                        new UniqueProductPrices(['groups' => ProductPriceCollectionType::VALIDATION_GROUP]),
                        new Valid(['groups' => ProductPriceCollectionType::VALIDATION_GROUP])
                    ],
                    'entry_options' => [
                        'product' => $product,
                    ],
                    'allow_add' => true,
                    'allow_delete' => true
                ]
            );

        $form
            ->expects($this->once())
            ->method('has')
            ->with('prices')
            ->willReturn(false);

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:' . ProductPrice::class)
            ->willReturn(true);

        $formEvent = new FormEvent($form, $product);
        $this->extension->addFormOnPreSetData($formEvent);
    }

    /**
     * @dataProvider addFormOnPreSetDataWithNoFieldAddedProviderAndUpdateProductProvider
     */
    public function testAddFormOnPreSetDataWithNoFieldAddedAndUpdateProduct(array $allowedPermissions)
    {
        $product = $this->getEntity(Product::class, ['id' => 1]);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->never())
            ->method('add');

        $form
            ->expects($this->once())
            ->method('has')
            ->with('prices')
            ->willReturn(false);

        $this->authorizationChecker
            ->expects($this->atLeast(2))
            ->method('isGranted')
            ->willReturnCallback(function ($permission, $objectDescriptions) use ($allowedPermissions) {
                if (!in_array($permission, $allowedPermissions)) {
                    return false;
                }

                return $objectDescriptions = 'entity:' . ProductPrice::class;
            });

        $formEvent = new FormEvent($form, $product);
        $this->extension->addFormOnPreSetData($formEvent);
    }

    /**
     * @return array
     */
    public function addFormOnPreSetDataWithNoFieldAddedProviderAndUpdateProductProvider()
    {
        return [
            'No "View" permission granted' => [
                'allowedPermissions' => ['EDIT', 'CREATE', 'DELETE'],
            ],
            'No "EDIT" permission granted' => [
                'allowedPermissions' => ['VIEW', 'CREATE', 'DELETE'],
            ],
        ];
    }

    /**
     * @dataProvider addFormOnPreSetDataAndFieldAddedAndUpdateProductProvider
     *
     * @param array $allowedPermissions
     * @param string $allowAdd
     * @param string $allowDelete
     */
    public function testAddFormOnPreSetDataAndFieldAddedAndUpdateProduct(
        array $allowedPermissions,
        $allowAdd,
        $allowDelete
    ) {
        $product = $this->getEntity(Product::class, ['id' => 1]);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('add')
            ->with(
                'prices',
                ProductPriceCollectionType::class,
                [
                    'label' => 'oro.pricing.productprice.entity_plural_label',
                    'required' => false,
                    'mapped' => false,
                    'constraints' => [
                        new UniqueProductPrices(['groups' => ProductPriceCollectionType::VALIDATION_GROUP]),
                        new Valid(['groups' => ProductPriceCollectionType::VALIDATION_GROUP])
                    ],
                    'entry_options' => [
                        'product' => $product,
                    ],
                    'allow_add' => $allowAdd,
                    'allow_delete' => $allowDelete
                ]
            );
        ;

        $form
            ->expects($this->once())
            ->method('has')
            ->with('prices')
            ->willReturn(false);

        $this->authorizationChecker
            ->expects($this->exactly(4))
            ->method('isGranted')
            ->willReturnCallback(function ($permission, $objectDescriptions) use ($allowedPermissions) {
                if (!in_array($permission, $allowedPermissions)) {
                    return false;
                }

                return $objectDescriptions = 'entity:' . ProductPrice::class;
            });

        $formEvent = new FormEvent($form, $product);
        $this->extension->addFormOnPreSetData($formEvent);
    }

    /**
     * @return array
     */
    public function addFormOnPreSetDataAndFieldAddedAndUpdateProductProvider()
    {
        return [
            'All permissions granted' => [
                'allowedPermissions' => ['EDIT', 'CREATE', 'DELETE', 'VIEW'],
                'allowAdd' => true,
                'allowDelete' => true
            ],
            'No "DELETE" permission granted' => [
                'allowedPermissions' => ['EDIT', 'CREATE', 'VIEW'],
                'allowAdd' => true,
                'allowDelete' => false
            ],
            'No "CREATE" permission granted' => [
                'allowedPermissions' => ['EDIT', 'VIEW', 'DELETE'],
                'allowAdd' => false,
                'allowDelete' => true
            ]
        ];
    }

    /**
     * @param Product|null $product
     *
     * @dataProvider onPostSetDataDataProvider
     */
    public function testOnPostSetData($product)
    {
        $event = $this->createEvent($product);
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
        $mainForm = $event->getForm();
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $pricesForm */
        $pricesForm = $mainForm->get('prices');

        if ($product && $product->getId()) {
            $prices = ['price1', 'price2'];

            $this->priceRepository
                ->expects(static::once())
                ->method('getPricesByProduct')
                ->with($this->shardManager, $product)
                ->willReturn($prices);

            $mainForm
                ->method('has')
                ->with('prices')
                ->willReturn(true);

            $pricesForm
                ->expects(static::once())
                ->method('setData')
                ->with($prices);
        } else {
            $this->priceRepository
                ->expects(static::never())
                ->method('getPricesByProduct');

            $mainForm
                ->expects($this->never())
                ->method('has')
                ->with('prices');
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
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm
            ->expects(static::never())
            ->method('isValid');

        $mainForm
            ->expects($this->never())
            ->method('has')
            ->with('prices');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitInvalidForm()
    {
        $event = $this->createEvent($this->createProduct());
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm
            ->expects(static::once())
            ->method('isValid')
            ->willReturn(false);

        $mainForm
            ->expects($this->once())
            ->method('has')
            ->with('prices')
            ->willReturn(true);

        $priceOne = $this->createProductPrice(1);
        $priceTwo = $this->createProductPrice(2);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $pricesForm */
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

        $this->em->expects($this->never())
            ->method('persist');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitFormWithoutPriceField()
    {
        $event = $this->createEvent($this->createProduct());
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm
            ->expects($this->never())
            ->method('isValid');

        $mainForm
            ->expects($this->once())
            ->method('has')
            ->with('prices')
            ->willReturn(false);

        $this->em->expects($this->never())
            ->method('persist');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitNewProduct()
    {
        $product = $this->createProduct();
        $event = $this->createEvent($product);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm
            ->expects($this->once())
            ->method('has')
            ->with('prices')
            ->willReturn(true);

        $priceList = new PriceList();
        $priceOne = $this->createProductPrice(1);
        $priceTwo = $this->createProductPrice(2);
        $priceOne->setPriceList($priceList);
        $priceTwo->setPriceList($priceList);

        $this->assertPriceAdd($event, [$priceOne, $priceTwo]);
        $this->priceRepository->expects($this->never())
            ->method('getPricesByProduct');

        $this->extension->onPostSubmit($event);

        $this->assertEquals($product, $priceOne->getProduct());
        $this->assertEquals($product, $priceTwo->getProduct());
    }

    public function testOnPostSubmitExistingProduct()
    {
        $product = $this->createProduct(1);
        $event = $this->createEvent($product);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm
            ->expects($this->once())
            ->method('has')
            ->with('prices')
            ->willReturn(true);

        $priceList = new PriceList();
        $priceOne = $this->createProductPrice(1);
        $priceTwo = $this->createProductPrice(2);
        $removedPrice = $this->createProductPrice(3);
        $priceOne->setPriceList($priceList);
        $priceTwo->setPriceList($priceList);
        $removedPrice->setPriceList($priceList);

        $this->assertPriceAdd($event, [$priceOne, $priceTwo]);
        $this->priceRepository->expects($this->once())
            ->method('getPricesByProduct')
            ->will($this->returnValue([$removedPrice]));

        $this->priceManager->expects($this->once())
            ->method('remove')
            ->with($removedPrice);

        $this->extension->onPostSubmit($event);

        $this->assertEquals($product, $priceOne->getProduct());
        $this->assertEquals($product, $priceTwo->getProduct());
    }

    /**
     * @param mixed $data
     *
     * @return FormEvent
     */
    protected function createEvent($data)
    {
        $pricesForm = $this->createMock('Symfony\Component\Form\FormInterface');

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
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
        return $this->getEntity(Product::class, ['id' => $id]);
    }

    /**
     * @param int|null $id
     *
     * @return ProductPrice
     */
    protected function createProductPrice($id = null)
    {
        return $this->getEntity(ProductPrice::class, ['id' => $id]);
    }

    protected function assertPriceAdd(FormEvent $event, array $prices)
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm
            ->expects(static::once())
            ->method('isValid')
            ->willReturn(true);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $pricesForm */
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
