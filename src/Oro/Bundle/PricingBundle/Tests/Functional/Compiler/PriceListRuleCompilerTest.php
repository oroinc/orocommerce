<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Compiler;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributeProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PriceListRuleCompilerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ManagerRegistry $doctrine;
    private PriceListRuleCompiler $compiler;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadPriceAttributeProductPrices::class,
            LoadCategoryProductData::class,
            LoadProductPrices::class,
            LoadOrganization::class
        ]);

        $this->doctrine = self::getContainer()->get('doctrine');
        $this->compiler = self::getContainer()->get('oro_pricing.compiler.price_list_rule_compiler');
    }

    /**
     * @dataProvider applyRuleConditionsDataProvider
     */
    public function testApplyRuleConditions(string $rule, string $expectedValue): void
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        /** @var Category $category1 */
        $category1 = $this->getReference(LoadCategoryData::FIRST_LEVEL);

        /** @var ProductUnit $unitLitre */
        $unitLitre = $this->getReference(LoadProductUnits::LITER);

        $condition = sprintf(
            "product.category == %d and product.price_attribute_price_list_1.currency == 'USD'",
            $category1->getId()
        );

        $priceList = $this->createPriceList();
        $this->assignProducts($priceList, [$product1, $product2]);

        $priceRule = $this->createPriceRule($priceList, $condition, $rule, 1, $unitLitre, 'USD');

        $expected = [
            'product' => $product1->getId(),
            'priceList' => $priceList->getId(),
            'unit' => $unitLitre->getCode(),
            'currency' => 'USD',
            'quantity' => '1.000',
            'productSku' => $product1->getSku(),
            'priceRule' => $priceRule->getId(),
            'value' => $expectedValue
        ];
        $qb = $this->getQueryBuilder($priceRule);
        $prices = $this->getActualResult($qb);
        $actual = reset($prices);
        unset($actual['id']);
        self::assertSame($expected, $actual);

        // Check that cache does not affect results
        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $price = reset($actual);
        unset($price['id']);
        self::assertSame($expected, $price);
    }

    public static function applyRuleConditionsDataProvider(): array
    {
        return [
            ['product.price_attribute_price_list_1.value * 10', '110.0000']
        ];
    }

    public function testValueRounding(): void
    {
        $configManager = self::getConfigManager();
        $initialPriceCalculationPrecision = $configManager->get('oro_pricing.price_calculation_precision');
        $configManager->set('oro_pricing.price_calculation_precision', 2);
        $configManager->flush();
        try {
            $this->testApplyRuleConditions('product.price_attribute_price_list_1.value/3', '3.67');
        } finally {
            $configManager->set('oro_pricing.price_calculation_precision', $initialPriceCalculationPrecision);
            $configManager->flush();
        }
    }

    public function testApplyRuleConditionsWithExpressions(): void
    {
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        $condition = sprintf(
            'product.category in [%s, %s]',
            $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId(),
            $this->getReference(LoadCategoryData::THIRD_LEVEL2)->getId()
        );
        $rule = 'product.price_attribute_price_list_1.value * 10';

        $priceList = $this->createPriceList();
        $this->assignProducts($priceList, [$product1, $product2]);
        $priceRule = new PriceRule();
        $priceRule->setCurrency('EUR')
            ->setPriceList($priceList)
            ->setPriority(1)
            ->setQuantity(1)
            ->setCurrencyExpression('product.price_attribute_price_list_1.currency')
            ->setProductUnitExpression('product.price_attribute_price_list_1.unit')
            ->setQuantityExpression('product.price_attribute_price_list_1.quantity + 5')
            ->setProductUnit($this->getReference(LoadProductUnits::LITER))
            ->setRuleCondition($condition)
            ->setRule($rule);

        $em = $this->doctrine->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        $expected = [
            [
                'product' => $product1->getId(),
                'priceList' => $priceList->getId(),
                'unit' => 'liter',
                'currency' => 'EUR',
                'quantity' => '6.000',
                'productSku' => 'product-1',
                'priceRule' => $priceRule->getId(),
                'value' => '100.0000'
            ],
            [
                'product' => $product1->getId(),
                'priceList' => $priceList->getId(),
                'unit' => 'liter',
                'currency' => 'USD',
                'quantity' => '6.000',
                'productSku' => 'product-1',
                'priceRule' => $priceRule->getId(),
                'value' => '110.0000'
            ],
            [
                'product' => $product1->getId(),
                'priceList' => $priceList->getId(),
                'unit' => 'bottle',
                'currency' => 'USD',
                'quantity' => '6.00',
                'productSku' => 'product-1',
                'priceRule' => $priceRule->getId(),
                'value' => '122.0000'
            ],
            [
                'product' => $product1->getId(),
                'priceList' => $priceList->getId(),
                'unit' => 'bottle',
                'currency' => 'EUR',
                'quantity' => '6.00',
                'productSku' => 'product-1',
                'priceRule' => $priceRule->getId(),
                'value' => '200.0000'
            ]
        ];

        $qb = $this->getQueryBuilder($priceRule);
        $prices = $this->getActualResult($qb);
        $actual = [];
        foreach ($prices as $price) {
            unset($price['id']);
            $actual[] = $price;
        }
        self::assertSame($expected, $actual);
    }

    public function testApplyRuleConditionsWithTwoBaseRelations(): void
    {
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        $condition = sprintf(
            'product.category == %s',
            $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId()
        );
        /** @var PriceList $basePriceList */
        $basePriceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        /** @var PriceList $basePriceList2 */
        $basePriceList2 = $this->getReference(LoadPriceLists::PRICE_LIST_2);

        $priceManager = self::getContainer()->get('oro_pricing.manager.price_manager');
        $price = (new ProductPrice())
            ->setUnit($this->getReference(LoadProductUnits::LITER))
            ->setPrice(Price::create('10', 'USD'))
            ->setQuantity(1)
            ->setPriceList($basePriceList2)
            ->setProduct($product1);
        $priceManager->persist($price);
        $priceManager->flush();

        $rule = sprintf(
            'pricelist[%s].prices.value + product.price_attribute_price_list_1.value + pricelist[%s].prices.value',
            $basePriceList->getId(),
            $basePriceList2->getId()
        );

        $priceList = $this->createPriceList();
        $this->assignProducts($priceList, [$product1, $product2]);
        $priceRule = new PriceRule();
        $priceRule
            ->setPriceList($priceList)
            ->setPriority(1)
            ->setCurrencyExpression(sprintf('pricelist[%s].prices.currency', $basePriceList->getId()))
            ->setProductUnitExpression(sprintf('pricelist[%s].prices.unit', $basePriceList->getId()))
            ->setQuantityExpression(sprintf('pricelist[%s].prices.quantity', $basePriceList->getId()))
            ->setRuleCondition($condition)
            ->setRule($rule);

        $em = $this->doctrine->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        $expected = [
            [
                'product' => $product1->getId(),
                'priceList' => $priceList->getId(),
                'unit' => 'liter',
                'currency' => 'USD',
                'quantity' => '1.000',
                'productSku' => 'product-1',
                'priceRule' => $priceRule->getId(),
                'value' => '31.0000'
            ]
        ];

        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEqualsPrices($expected, $actual);
    }

    public function testApplyRuleConditionsWithPriceListRelationAndStaticValues(): void
    {
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        /** @var PriceList $basePriceList */
        $basePriceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $rule = sprintf(
            'pricelist[%s].prices.value * 100',
            $basePriceList->getId()
        );

        $priceList = $this->createPriceList();
        $this->assignProducts($priceList, [$product1, $product2]);
        $priceRule = new PriceRule();
        $priceRule
            ->setPriceList($priceList)
            ->setPriority(1)
            ->setCurrency('USD')
            ->setProductUnit($this->getReference(LoadProductUnits::LITER))
            ->setQuantity(10)
            ->setRule($rule);

        $em = $this->doctrine->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        $expected = [
            [
                'product' => $product1->getId(),
                'priceList' => $priceList->getId(),
                'unit' => 'liter',
                'currency' => 'USD',
                'quantity' => '10.000',
                'productSku' => 'product-1',
                'priceRule' => $priceRule->getId(),
                'value' => '1220.0000'
            ]
        ];

        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEqualsPrices($expected, $actual);
    }

    public function testApplyRuleConditionsWithPriceAttributeRelationAndStaticValues(): void
    {
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        $priceList = $this->createPriceList();
        $this->assignProducts($priceList, [$product1, $product2]);
        $priceRule = new PriceRule();
        $priceRule
            ->setPriceList($priceList)
            ->setPriority(1)
            ->setCurrency('USD')
            ->setProductUnit($this->getReference(LoadProductUnits::LITER))
            ->setQuantity(5)
            ->setRule('product.price_attribute_price_list_1.value * 50');

        $em = $this->doctrine->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        $expected = [
            [
                'product' => $product1->getId(),
                'priceList' => $priceList->getId(),
                'unit' => 'liter',
                'currency' => 'USD',
                'quantity' => '5.000',
                'productSku' => 'product-1',
                'priceRule' => $priceRule->getId(),
                'value' => '550.0000'
            ],
            [
                'product' => $product2->getId(),
                'priceList' => $priceList->getId(),
                'unit' => 'liter',
                'currency' => 'USD',
                'quantity' => '5.000',
                'productSku' => 'product-2',
                'priceRule' => $priceRule->getId(),
                'value' => '1000.0000'
            ]
        ];

        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEqualsPrices($expected, $actual);
    }

    public function testApplyRuleConditionsWithExpressionsAndDefinedValues(): void
    {
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        $condition = sprintf(
            'product.category == %s and product.price_attribute_price_list_1.currency == "%s"',
            $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId(),
            'USD'
        );
        $rule = 'product.price_attribute_price_list_1.value * 10';

        $priceList = $this->createPriceList();
        $this->assignProducts($priceList, [$product1, $product2]);
        $priceRule = new PriceRule();
        $priceRule
            ->setCurrency('EUR')
            ->setPriceList($priceList)
            ->setPriority(1)
            ->setQuantity(1)
            ->setProductUnitExpression('product.price_attribute_price_list_1.unit')
            ->setQuantityExpression('product.price_attribute_price_list_1.quantity + 5.0009')
            ->setProductUnit($this->getReference(LoadProductUnits::LITER))
            ->setRuleCondition($condition)
            ->setRule($rule);

        $em = $this->doctrine->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        $expected = [
            [
                'product' => $product1->getId(),
                'priceList' => $priceList->getId(),
                'unit' => 'liter',
                'currency' => 'EUR',
                'quantity' => '6.001',
                'productSku' => 'product-1',
                'priceRule' => $priceRule->getId(),
                'value' => '110.0000'
            ],
            [
                'product' => $product1->getId(),
                'priceList' => $priceList->getId(),
                'unit' => 'bottle',
                'currency' => 'EUR',
                'quantity' => '6.00',
                'productSku' => 'product-1',
                'priceRule' => $priceRule->getId(),
                'value' => '122.0000'
            ]
        ];

        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEqualsPrices($expected, $actual);
    }

    public function testRestrictByManualPrices(): void
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        /** @var ProductUnit $unitLitre */
        $unitLitre = $this->getReference(LoadProductUnits::LITER);

        $condition = null;
        $rule = '420';

        $priceList = $this->createPriceList();
        $this->assignProducts($priceList, [$product1, $product2]);

        $priceRule = $this->createPriceRule($priceList, $condition, $rule, 1, $unitLitre, 'EUR');

        $manualPrice = new ProductPrice();
        $manualPrice->setPriceList($priceList)
            ->setProduct($product2)
            ->setQuantity(1)
            ->setUnit($unitLitre)
            ->setPrice(Price::create(500, 'EUR'));
        $priceManager = self::getContainer()->get('oro_pricing.manager.price_manager');
        $priceManager->persist($manualPrice);
        $priceManager->flush();

        $expected = [
            [
                'product' => $product1->getId(),
                'priceList' => $priceList->getId(),
                'unit' => $unitLitre->getCode(),
                'currency' => 'EUR',
                'quantity' => '1.000',
                'productSku' => $product1->getSku(),
                'priceRule' => $priceRule->getId(),
                'value' => 420
            ]
        ];

        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEqualsPrices($expected, $actual);
    }

    public function testRestrictByProduct(): void
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        /** @var ProductUnit $unitLitre */
        $unitLitre = $this->getReference(LoadProductUnits::LITER);

        $condition = null;
        $rule = '420';

        $priceList = $this->createPriceList();
        $this->assignProducts($priceList, [$product1, $product2]);

        $priceRule = $this->createPriceRule($priceList, $condition, $rule, 1, $unitLitre, 'EUR');

        $expected = [
            [
                'product' => $product1->getId(),
                'priceList' => $priceList->getId(),
                'unit' => $unitLitre->getCode(),
                'currency' => 'EUR',
                'quantity' => '1.000',
                'productSku' => $product1->getSku(),
                'priceRule' => $priceRule->getId(),
                'value' => 420
            ]
        ];

        $qb = $this->getQueryBuilder($priceRule, [$product1]);
        $actual = $this->getActualResult($qb);
        $this->assertEqualsPrices($expected, $actual);
    }

    public function testRestrictByAssignedProducts(): void
    {
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        /** @var Category $category1 */
        $category1 = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        /** @var Category $category2 */
        $category2 = $this->getReference(LoadCategoryData::SECOND_LEVEL1);

        /** @var ProductUnit $unitLitre */
        $unitLitre = $this->getReference(LoadProductUnits::LITER);

        $condition = '(product.category == '.$category1->getId()
            .' or product.category == '.$category2->getId().')'
            ." and product.price_attribute_price_list_1.currency == 'USD'";

        $rule = 'product.price_attribute_price_list_1.value * 10';

        $priceList = $this->createPriceList();
        $this->assignProducts($priceList, [$product2]);

        $priceRule = $this->createPriceRule($priceList, $condition, $rule, 1, $unitLitre, 'EUR');
        $qb = $this->getQueryBuilder($priceRule);

        $expected = [
            [
                'product' => $product2->getId(),
                'priceList' => $priceList->getId(),
                'unit' => $unitLitre->getCode(),
                'currency' => 'EUR',
                'quantity' => '1.000',
                'productSku' => $product2->getSku(),
                'priceRule' => $priceRule->getId(),
                'value' => '200.0000'
            ]
        ];
        $actual = $this->getActualResult($qb);
        $this->assertEqualsPrices($expected, $actual);
    }

    public function testRestrictByProductUnit(): void
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        /** @var ProductUnit $unit */
        $unit = $this->getReference(LoadProductUnits::BOX);

        $rule = '10';

        $priceList = $this->createPriceList();
        $this->assignProducts($priceList, [$product1, $product2]);

        $priceRule = $this->createPriceRule($priceList, null, $rule, 1, $unit, 'EUR');

        $expected = [
            [
                'product' => $product2->getId(),
                'priceList' => $priceList->getId(),
                'unit' => $unit->getCode(),
                'currency' => 'EUR',
                'quantity' => '1.0',
                'productSku' => $product2->getSku(),
                'priceRule' => $priceRule->getId(),
                'value' => 10
            ]
        ];
        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEqualsPrices($expected, $actual);

        // Check that cache does not affect results
        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEqualsPrices($expected, $actual);
    }

    public function testNotIn(): void
    {
        /** @var Product $product3 */
        $product3 = $this->getReference(LoadProductData::PRODUCT_3);
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        /** @var ProductUnit $unitLitre */
        $unitLitre = $this->getReference(LoadProductUnits::LITER);

        $mainPriceList = $this->createPriceList();
        $this->assignProducts($mainPriceList, [$product2]);

        $priceList = $this->createPriceList();
        $this->assignProducts($priceList, [$product3, $product2]);

        $priceRule = new PriceRule();
        $priceRule
            ->setCurrencyExpression('product.price_attribute_price_list_1.currency')
            ->setPriceList($priceList)
            ->setPriority(1)
            ->setQuantityExpression('product.price_attribute_price_list_1.quantity')
            ->setProductUnitExpression('product.price_attribute_price_list_1.unit')
            ->setRuleCondition(sprintf('product not in pricelist[%d].assignedProducts', $mainPriceList->getId()))
            ->setRule(10);

        $em = $this->doctrine->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        $expected = [
            [
                'product' => $product3->getId(),
                'priceList' => $priceList->getId(),
                'unit' => $unitLitre->getCode(),
                'currency' => 'USD',
                'quantity' => '1.000',
                'productSku' => $product3->getSku(),
                'priceRule' => $priceRule->getId(),
                'value' => 10
            ]
        ];
        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEqualsPrices($expected, $actual);
    }

    public function testIn(): void
    {
        /** @var Product $product3 */
        $product3 = $this->getReference(LoadProductData::PRODUCT_3);
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        /** @var ProductUnit $unitLitre */
        $unitLitre = $this->getReference(LoadProductUnits::LITER);

        $mainPriceList = $this->createPriceList();
        $this->assignProducts($mainPriceList, [$product3]);

        $priceList = $this->createPriceList();
        $this->assignProducts($priceList, [$product3, $product2]);

        $priceRule = new PriceRule();
        $priceRule
            ->setCurrencyExpression('product.price_attribute_price_list_1.currency')
            ->setPriceList($priceList)
            ->setPriority(1)
            ->setQuantityExpression('product.price_attribute_price_list_1.quantity')
            ->setProductUnitExpression('product.price_attribute_price_list_1.unit')
            ->setRuleCondition(sprintf('product in pricelist[%d].assignedProducts', $mainPriceList->getId()))
            ->setRule(10);

        $em = $this->doctrine->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        $expected = [
            [
                'product' => $product3->getId(),
                'priceList' => $priceList->getId(),
                'unit' => $unitLitre->getCode(),
                'currency' => 'USD',
                'quantity' => '1.000',
                'productSku' => $product3->getSku(),
                'priceRule' => $priceRule->getId(),
                'value' => 10
            ]
        ];
        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEqualsPrices($expected, $actual);
    }

    public function testProductAssignmentRuleReferencing(): void
    {
        /** @var Product $product3 */
        $product3 = $this->getReference(LoadProductData::PRODUCT_3);
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        /** @var ProductUnit $unitLitre */
        $unitLitre = $this->getReference(LoadProductUnits::LITER);

        $mainPriceList = $this->createPriceList();
        $mainPriceList->setProductAssignmentRule('product.id == ' . $product3->getId());

        $priceList = $this->createPriceList();
        $this->assignProducts($priceList, [$product3, $product2]);

        $priceRule = new PriceRule();
        $priceRule
            ->setCurrencyExpression('product.price_attribute_price_list_1.currency')
            ->setPriceList($priceList)
            ->setPriority(1)
            ->setQuantityExpression('product.price_attribute_price_list_1.quantity')
            ->setProductUnitExpression('product.price_attribute_price_list_1.unit')
            ->setRuleCondition(sprintf('pricelist[%d].productAssignmentRule', $mainPriceList->getId()))
            ->setRule(10);

        $em = $this->doctrine->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        $expected = [
            [
                'product' => $product3->getId(),
                'priceList' => $priceList->getId(),
                'unit' => $unitLitre->getCode(),
                'currency' => 'USD',
                'quantity' => '1.000',
                'productSku' => $product3->getSku(),
                'priceRule' => $priceRule->getId(),
                'value' => 10
            ]
        ];
        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEqualsPrices($expected, $actual);
    }

    public function testRuleUnsupportedCurrency(): void
    {
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        $priceList = $this->createPriceList();
        $this->assignProducts($priceList, [$product2]);

        $priceRule = new PriceRule();
        $priceRule
            ->setCurrency('UAH')
            ->setPriceList($priceList)
            ->setPriority(1)
            ->setQuantity(1)
            ->setProductUnitExpression('product.price_attribute_price_list_1.unit')
            ->setRule(10);

        $em = $this->doctrine->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEmpty($actual);
    }

    private function createPriceList(): PriceList
    {
        $priceList = new PriceList();
        $priceList->setActive(true)
            ->setCurrencies(['USD', 'EUR'])
            ->setName('Test Assignment Rules Price List')
            ->setOrganization($this->getReference('organization'));
        $em = $this->doctrine->getManagerForClass(PriceList::class);
        $em->persist($priceList);
        $em->flush();

        return $priceList;
    }

    private function getQueryBuilder(PriceRule $priceRule, array $products = []): QueryBuilder
    {
        $qb = $this->compiler->compile($priceRule, $products);
        $aliases = $qb->getRootAliases();
        $rootAlias = reset($aliases);
        $qb->orderBy($rootAlias.'.id');

        return $qb;
    }

    private function getActualResult(QueryBuilder $qb): array
    {
        $query = $qb->getQuery();

        $shardManager = self::getContainer()->get('oro_pricing.shard_manager');
        $query->setHint(PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER, $shardManager);

        $result = $query->getResult();
        usort($result, function (array $a, array $b) {
            return $a['value'] <=> $b['value'];
        });

        return $result;
    }

    private function createPriceRule(
        PriceList $priceList,
        ?string $condition,
        string $rule,
        float $qty,
        ProductUnit $unit,
        string $currency,
        int $priority = 1
    ): PriceRule {
        $priceRule = new PriceRule();
        $priceRule->setCurrency($currency)
            ->setPriceList($priceList)
            ->setPriority($priority)
            ->setQuantity($qty)
            ->setProductUnit($unit)
            ->setRuleCondition($condition)
            ->setRule($rule);

        $em = $this->doctrine->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        return $priceRule;
    }

    /**
     * @param PriceList $priceList
     * @param Product[] $products
     */
    private function assignProducts(PriceList $priceList, array $products): void
    {
        $em = $this->doctrine->getManagerForClass(PriceListToProduct::class);
        foreach ($products as $product) {
            $assignment = new PriceListToProduct();
            $assignment->setPriceList($priceList)
                ->setProduct($product);
            $em->persist($assignment);
        }

        $em->flush();
    }

    private function assertEqualsPrices(array $expected, array $actual): void
    {
        $this->assertCount(count($expected), $actual);
        foreach ($actual as $key => $price) {
            $this->assertArrayHasKey('id', $price);
            unset($price['id']);
            self::assertArrayHasKey($key, $expected);
            self::assertSame($expected[$key], $price);
        }
    }
}
