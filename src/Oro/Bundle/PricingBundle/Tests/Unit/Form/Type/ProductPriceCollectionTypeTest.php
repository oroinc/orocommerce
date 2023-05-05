<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Form\Type\ProductPriceCollectionType;
use Oro\Bundle\PricingBundle\Form\Type\ProductPriceType;
use Oro\Bundle\PricingBundle\Form\Type\ProductPriceUnitSelectorType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
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

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ProductPriceCollectionType */
    private $formType;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->formType = new ProductPriceCollectionType($this->registry);
        $this->formType->setDataClass(ProductPrice::class);
        $this->formType->setPriceListClass(PriceList::class);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $entityManager = $this->createMock(EntityManager::class);
        $searchRegistry = $this->createMock(SearchRegistry::class);

        $priceType = new PriceType();
        $priceType->setDataClass(Price::class);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    new CollectionType(),
                    new ProductPriceType(),
                    PriceListSelectType::class => new PriceListSelectTypeStub(),
                    new OroEntitySelectOrCreateInlineType(
                        $this->createMock(AuthorizationCheckerInterface::class),
                        $this->createMock(FeatureChecker::class),
                        $this->createMock(ConfigManager::class),
                        $entityManager,
                        $searchRegistry
                    ),
                    ProductPriceUnitSelectorType::class => new ProductUnitSelectionTypeStub(
                        $this->prepareProductUnitSelectionChoices(['item', 'set'])
                    ),
                    new OroJquerySelect2HiddenType(
                        $entityManager,
                        $searchRegistry,
                        $this->createMock(ConfigProvider::class)
                    ),
                    $priceType,
                    $this->getQuantityType(),
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
        $this->assertFalse($options['show_form_when_empty']);
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
            ->willReturn($currencies);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repository);

        $form = $this->factory->create(ProductPriceCollectionType::class);
        $view = $form->createView();

        $this->assertEquals(
            json_encode($currencies, JSON_THROW_ON_ERROR),
            $view->vars['attr']['data-currencies']
        );
    }

    private function prepareProductUnitSelectionChoices(array $units): array
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
