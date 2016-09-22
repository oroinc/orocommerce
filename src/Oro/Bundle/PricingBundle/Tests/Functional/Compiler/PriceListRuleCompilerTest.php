<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Compiler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributeProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PriceListRuleCompilerTest extends WebTestCase
{
    /**
     * @var PriceListRuleCompiler
     */
    protected $compiler;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                LoadPriceAttributeProductPrices::class,
                LoadCategoryProductData::class,
                LoadProductPrices::class
            ]
        );

        $this->registry = $this->getContainer()->get('doctrine');
        $this->compiler = $this->getContainer()->get('oro_pricing.compiler.price_list_rule_compiler');
    }

    public function testApplyRuleConditions()
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        /** @var Category $category1 */
        $category1 = $this->getReference(LoadCategoryData::FIRST_LEVEL);

        /** @var ProductUnit $unitLitre */
        $unitLitre = $this->getReference(LoadProductUnits::LITER);

        $condition = 'product.category == '.$category1->getId()
            ." and product.price_attribute_price_list_1.currency == 'USD'";

        $rule = 'product.price_attribute_price_list_1.value * 10';

        $priceList = $this->createPriceList();
        $this->assignProducts($priceList, [$product1, $product2]);

        $priceRule = $this->createPriceRule($priceList, $condition, $rule, 1, $unitLitre, 'USD');

        $expected = [
            [
                $product1->getId(),
                $priceList->getId(),
                $unitLitre->getCode(),
                'USD',
                1,
                $product1->getSku(),
                $priceRule->getId(),
                110,
            ],
        ];
        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEquals($expected, $actual);

        // Check that cache does not affect results
        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEquals($expected, $actual);
    }

    public function testApplyRuleConditionsWithExpressions()
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

        $em = $this->registry->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        $expected = [
            [
                $product1->getId(),
                $priceList->getId(),
                'bottle',
                'EUR',
                '6',
                'product.1',
                $priceRule->getId(),
                '200.0000',
            ],
            [
                $product1->getId(),
                $priceList->getId(),
                'bottle',
                'USD',
                '6',
                'product.1',
                $priceRule->getId(),
                '122.0000',
            ],
            [
                $product1->getId(),
                $priceList->getId(),
                'liter',
                'EUR',
                '6',
                'product.1',
                $priceRule->getId(),
                '100.0000',
            ],
            [
                $product1->getId(),
                $priceList->getId(),
                'liter',
                'USD',
                '6',
                'product.1',
                $priceRule->getId(),
                '110.0000',
            ],
        ];

        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEquals($expected, $actual, '', 0.0, 10, true);
    }

    public function testApplyRuleConditionsWithTwoBaseRelations()
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
        $price = (new ProductPrice())
            ->setUnit($this->getReference(LoadProductUnits::LITER))
            ->setPrice(Price::create('10', 'USD'))
            ->setQuantity(1)
            ->setPriceList($basePriceList2)
            ->setProduct($product1);
        $em = $this->registry->getManagerForClass(PriceList::class);
        $em->persist($price);
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

        $em = $this->registry->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        $expected = [
            [
                $product1->getId(),
                $priceList->getId(),
                'liter',
                'USD',
                1,
                'product.1',
                $priceRule->getId(),
                '31.0000',
            ],
        ];


        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEquals($expected, $actual, '', 0.0, 10, true);
    }

    public function testApplyRuleConditionsWithExpressionsAndDefinedValues()
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
            ->setQuantityExpression('product.price_attribute_price_list_1.quantity + 5')
            ->setProductUnit($this->getReference(LoadProductUnits::LITER))
            ->setRuleCondition($condition)
            ->setRule($rule);

        $em = $this->registry->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        $expected = [
            [
                $product1->getId(),
                $priceList->getId(),
                'bottle',
                'EUR',
                '6',
                'product.1',
                $priceRule->getId(),
                '122.0000',
            ],
            [
                $product1->getId(),
                $priceList->getId(),
                'liter',
                'EUR',
                '6',
                'product.1',
                $priceRule->getId(),
                '110.0000',
            ],
        ];

        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEquals($expected, $actual, '', 0.0, 10, true);
    }

    public function testRestrictByManualPrices()
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
        $em = $this->registry->getManagerForClass(ProductPrice::class);
        $em->persist($manualPrice);
        $em->flush();

        $expected = [
            [
                $product1->getId(),
                $priceList->getId(),
                $unitLitre->getCode(),
                'EUR',
                1,
                $product1->getSku(),
                $priceRule->getId(),
                420,
            ],
        ];

        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEquals($expected, $actual);
    }

    public function testRestrictByProduct()
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
                $product1->getId(),
                $priceList->getId(),
                $unitLitre->getCode(),
                'EUR',
                1,
                $product1->getSku(),
                $priceRule->getId(),
                420,
            ],
        ];

        $qb = $this->getQueryBuilder($priceRule, $product1);
        $actual = $this->getActualResult($qb);
        $this->assertEquals($expected, $actual);
    }

    public function testRestrictByAssignedProducts()
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
                $product2->getId(),
                $priceList->getId(),
                $unitLitre->getCode(),
                'EUR',
                1,
                $product2->getSku(),
                $priceRule->getId(),
                200,
            ],
        ];
        $actual = $this->getActualResult($qb);
        $this->assertEquals($expected, $actual);
    }

    public function testRestrictByProductUnit()
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
                $product2->getId(),
                $priceList->getId(),
                $unit->getCode(),
                'EUR',
                1,
                $product2->getSku(),
                $priceRule->getId(),
                10,
            ],
        ];
        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEquals($expected, $actual);

        // Check that cache does not affect results
        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEquals($expected, $actual);
    }

    public function testNotIn()
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

        $em = $this->registry->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        $expected = [
            [
                $product3->getId(),
                $priceList->getId(),
                $unitLitre->getCode(),
                'USD',
                1,
                $product3->getSku(),
                $priceRule->getId(),
                10
            ],
        ];
        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEquals($expected, $actual);
    }

    public function testIn()
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

        $em = $this->registry->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        $expected = [
            [
                $product3->getId(),
                $priceList->getId(),
                $unitLitre->getCode(),
                'USD',
                1,
                $product3->getSku(),
                $priceRule->getId(),
                10
            ],
        ];
        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEquals($expected, $actual);
    }

    public function testProductAssignmentRuleReferencing()
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

        $em = $this->registry->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        $expected = [
            [
                $product3->getId(),
                $priceList->getId(),
                $unitLitre->getCode(),
                'USD',
                1,
                $product3->getSku(),
                $priceRule->getId(),
                10
            ],
        ];
        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEquals($expected, $actual);
    }

    public function testRuleUnsupportedCurrency()
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

        $em = $this->registry->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        $qb = $this->getQueryBuilder($priceRule);
        $actual = $this->getActualResult($qb);
        $this->assertEmpty($actual);
    }

    /**
     * @return PriceList
     */
    protected function createPriceList()
    {
        $priceList = new PriceList();
        $priceList->setActive(true)
            ->setCurrencies(['USD', 'EUR'])
            ->setName('Test Assignment Rules Price List');
        $em = $this->registry->getManagerForClass(PriceList::class);
        $em->persist($priceList);
        $em->flush();

        return $priceList;
    }

    /**
     * @param PriceRule $priceRule
     * @param Product|null $product
     * @return QueryBuilder
     */
    protected function getQueryBuilder(PriceRule $priceRule, Product $product = null)
    {
        $qb = $this->compiler->compile($priceRule, $product);
        $aliases = $qb->getRootAliases();
        $rootAlias = reset($aliases);
        $qb->orderBy($rootAlias.'.id');

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @return array
     */
    protected function getActualResult(QueryBuilder $qb)
    {
        $actual = $qb->getQuery()->getArrayResult();
        $actual = array_map(
            function (array $value) {
                return array_values($value);
            },
            $actual
        );

        return $actual;
    }

    /**
     * @param PriceList $priceList
     * @param string $condition
     * @param string $rule
     * @param float $qty
     * @param ProductUnit $unit
     * @param string $currency
     * @param int $priority
     * @return PriceRule
     */
    protected function createPriceRule(
        PriceList $priceList,
        $condition,
        $rule,
        $qty,
        ProductUnit $unit,
        $currency,
        $priority = 1
    ) {
        $priceRule = new PriceRule();
        $priceRule->setCurrency($currency)
            ->setPriceList($priceList)
            ->setPriority($priority)
            ->setQuantity($qty)
            ->setProductUnit($unit)
            ->setRuleCondition($condition)
            ->setRule($rule);

        $em = $this->registry->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        return $priceRule;
    }

    /**
     * @param PriceList $priceList
     * @param Product[] $products
     */
    protected function assignProducts(PriceList $priceList, array $products)
    {
        $em = $this->registry->getManagerForClass(PriceListToProduct::class);
        foreach ($products as $product) {
            $assignment = new PriceListToProduct();
            $assignment->setPriceList($priceList)
                ->setProduct($product);
            $em->persist($assignment);
        }

        $em->flush();
    }
}
