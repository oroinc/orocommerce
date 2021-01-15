<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type\PriceTypeGenerator;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Form\Type\ProductPriceCollectionType;
use Oro\Bundle\PricingBundle\Form\Type\ProductPriceType;
use Oro\Bundle\PricingBundle\Form\Type\ProductPriceUnitSelectorType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Component\Testing\Unit\Form\Extension\Stub\FormTypeValidatorExtensionStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ProductPriceCollectionTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait;

    const PRICE_LIST_CLASS = 'Oro\Bundle\PricingBundle\Entity\PriceList';

    /**
     * @var ProductPriceCollectionType
     */
    protected $formType;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->formType = new ProductPriceCollectionType($this->registry);
        $this->formType->setDataClass(ProductPrice::class);
        $this->formType->setPriceListClass(self::PRICE_LIST_CLASS);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        unset($this->formType);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject $authorizationChecker */
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $authorizationChecker */
        $configManager = $this->createMock(ConfigManager::class);

        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $authorizationChecker */
        $entityManager = $this->createMock(EntityManager::class);

        /** @var SearchRegistry|\PHPUnit\Framework\MockObject\MockObject $authorizationChecker */
        $searchRegistry = $this->createMock(SearchRegistry::class);

        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $authorizationChecker */
        $configProvider = $this->createMock(ConfigProvider::class);

        $productUnitSelection = new ProductUnitSelectionTypeStub(
            $this->prepareProductUnitSelectionChoices(['item', 'set']),
            ProductPriceUnitSelectorType::NAME
        );

        $priceType = PriceTypeGenerator::createPriceType($this);

        return [
            new PreloadedExtension(
                [
                    ProductPriceCollectionType::class => $this->formType,
                    CollectionType::class => new CollectionType(),
                    ProductPriceType::class => new ProductPriceType(),
                    PriceListSelectType::class => new PriceListSelectTypeStub(),
                    OroEntitySelectOrCreateInlineType::class => new OroEntitySelectOrCreateInlineType(
                        $authorizationChecker,
                        $configManager,
                        $entityManager,
                        $searchRegistry
                    ),
                    ProductPriceUnitSelectorType::class => $productUnitSelection,
                    OroJquerySelect2HiddenType::class => new OroJquerySelect2HiddenType(
                        $entityManager,
                        $searchRegistry,
                        $configProvider
                    ),
                    PriceType::class => $priceType,
                    QuantityType::class => $this->getQuantityType(),
                    CurrencySelectionType::class => new CurrencySelectionTypeStub()
                ],
                [
                    FormType::class => [
                        new FormTypeValidatorExtensionStub()
                    ]
                ]
            )
        ];
    }

    public function testGetParent()
    {
        $this->assertIsString($this->formType->getParent());
        $this->assertEquals(CollectionType::class, $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        $form = $this->factory->create(ProductPriceCollectionType::class);

        $options = $form->getConfig()->getOptions();

        $this->assertSame(ProductPriceType::class, $options['entry_type']);
        $this->assertSame(false, $options['show_form_when_empty']);
        $this->assertSame(ProductPrice::class, $options['entry_options']['data_class']);
    }

    public function testFinishView()
    {
        $currencies = [
            '1' => ['EUR', 'USD'],
            '2' => ['CAD', 'USD']
        ];

        $repository = $this->createMock(PriceListRepository::class);

        $repository->expects($this->once())
            ->method('getCurrenciesIndexedByPricelistIds')
            ->will($this->returnValue($currencies));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(self::PRICE_LIST_CLASS)
            ->will($this->returnValue($repository));

        $form = $this->factory->create(ProductPriceCollectionType::class);
        $view = $form->createView();

        $this->assertEquals(
            json_encode($currencies),
            $view->vars['attr']['data-currencies']
        );
    }

    /**
     * @param array $units
     * @return array
     */
    private function prepareProductUnitSelectionChoices(array $units)
    {
        $choices = [];
        foreach ($units as $unitCode) {
            $unit = new ProductUnit();
            $unit->setCode($unitCode);
            $choices[$unitCode] = $unit;
        }

        return $choices;
    }
}
