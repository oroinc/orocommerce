<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type\PriceTypeGenerator;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\FormBundle\Form\Type\Select2Type;
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
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->formType = new ProductPriceCollectionType($this->registry);
        $this->formType->setDataClass(ProductPrice::class);
        $this->formType->setPriceListClass(self::PRICE_LIST_CLASS);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->formType);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /** @var AuthorizationCheckerInterface|\PHPUnit_Framework_MockObject_MockObject $authorizationChecker */
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject $authorizationChecker */
        $configManager = $this->createMock(ConfigManager::class);

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $authorizationChecker */
        $entityManager = $this->createMock(EntityManager::class);

        /** @var SearchRegistry|\PHPUnit_Framework_MockObject_MockObject $authorizationChecker */
        $searchRegistry = $this->createMock(SearchRegistry::class);

        /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject $authorizationChecker */
        $configProvider = $this->createMock(ConfigProvider::class);

        $productUnitSelection = new ProductUnitSelectionTypeStub(
            $this->prepareProductUnitSelectionChoices(['item', 'set']),
            ProductPriceUnitSelectorType::NAME
        );

        $priceType = PriceTypeGenerator::createPriceType($this);

        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME => new CollectionType(),
                    ProductPriceType::NAME => new ProductPriceType(),
                    PriceListSelectType::NAME => new PriceListSelectTypeStub(),
                    OroEntitySelectOrCreateInlineType::NAME => new OroEntitySelectOrCreateInlineType(
                        $authorizationChecker,
                        $configManager,
                        $entityManager,
                        $searchRegistry
                    ),
                    ProductPriceUnitSelectorType::NAME => $productUnitSelection,
                    OroJquerySelect2HiddenType::NAME => new OroJquerySelect2HiddenType(
                        $entityManager,
                        $searchRegistry,
                        $configProvider
                    ),
                    'oro_select2_hidden' => new Select2Type(
                        'Symfony\Component\Form\Extension\Core\Type\HiddenType',
                        'oro_select2_hidden'
                    ),
                    PriceType::NAME => $priceType,
                    QuantityType::NAME => $this->getQuantityType(),
                    CurrencySelectionType::class => new CurrencySelectionTypeStub()
                ],
                [
                    'form' => [
                        new FormTypeValidatorExtensionStub()
                    ]
                ]
            )
        ];
    }

    public function testGetParent()
    {
        $this->assertInternalType('string', $this->formType->getParent());
        $this->assertEquals(CollectionType::NAME, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->formType->getName());
        $this->assertEquals(ProductPriceCollectionType::NAME, $this->formType->getName());
    }

    public function testConfigureOptions()
    {
        $form = $this->factory->create($this->formType);

        $expectedOptions = [
            'entry_type' => ProductPriceType::NAME,
            'show_form_when_empty' => false,
            'entry_options' => ['data_class' => ProductPrice::class]
        ];

        $this->assertArraySubset($expectedOptions, $form->getConfig()->getOptions());
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

        $form = $this->factory->create($this->formType);
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
