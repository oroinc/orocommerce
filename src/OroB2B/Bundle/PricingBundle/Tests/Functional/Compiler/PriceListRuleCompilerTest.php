<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Compiler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use OroB2B\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToProduct;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributeProductPrices;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
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
            ]
        );

        $this->registry = $this->getContainer()->get('doctrine');
        $this->compiler = $this->getContainer()->get('orob2b_pricing.compiler.price_list_rule_compiler');
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
        $unitLitre = $this->getReference('product_unit.liter');

        $condition = 'product.category == ' . $category1->getId()
            . " and product.price_attribute_price_list_1.currency == 'USD'";

        $rule = 'product.price_attribute_price_list_1.value * 10';

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
                100
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

    public function testRestrictByManualPrices()
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        /** @var ProductUnit $unitLitre */
        $unitLitre = $this->getReference('product_unit.liter');

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
                420
            ]
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
        $unitLitre = $this->getReference('product_unit.liter');

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
                420
            ]
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
        $unitLitre = $this->getReference('product_unit.liter');

        $condition = '(product.category == ' . $category1->getId()
            . ' or product.category == ' . $category2->getId() . ')'
            . " and product.price_attribute_price_list_1.currency == 'USD'";

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
                200
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
        $unit = $this->getReference('product_unit.box');

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
                10
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
        $qb->orderBy($rootAlias . '.id');

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
