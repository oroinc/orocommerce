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
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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
            'product.category == %s',
            $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId()
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
            ->setProductUnitExpression('product.unitPrecisions.unit')
            ->setQuantityExpression('product.price_attribute_price_list_1.quantity + 5')
            ->setProductUnit($this->getReference('product_unit.liter'))
            ->setRuleCondition($condition)
            ->setRule($rule);

        $em = $this->registry->getManagerForClass(PriceRule::class);
        $em->persist($priceRule);
        $em->flush();

        $expected = [];

        /**
        SELECT DISTINCT
        o0_.id                    AS product,
        o2_.price_attribute_pl_id AS price_attribute,
        --   448                       AS sclr_1,
        o1_.unit_code             AS unit,
        o2_.currency              AS currency,
        o2_.quantity + 5             AS quantity,
        o0_.sku                   AS sku,
        --   75                        AS sclr_6,
        o2_.value * 10            AS price
        FROM orob2b_product o0_
        LEFT JOIN orob2b_catalog_category o3_ ON (
        EXISTS(
        SELECT 1
        FROM orob2b_category_to_product o4_
        INNER JOIN orob2b_product o5_ ON o4_.product_id = o5_.id
        WHERE o4_.category_id = o3_.id AND o5_.id IN (o0_.id)))
        LEFT JOIN orob2b_price_attribute_price o2_ ON ((o2_.product_id = o0_.id AND o2_.price_attribute_pl_id = 1))
        LEFT JOIN orob2b_product_unit_precision o1_ ON (o0_.id = o1_.product_id AND EXISTS (
        SELECT 1
        FROM orob2b_price_attribute_price o7_
        WHERE
        o7_.unit_code = o1_.unit_code AND
        o7_.unit_code = o2_.unit_code AND
        o7_.product_id = o0_.id AND
        o7_.currency = o2_.currency
        ))
        INNER JOIN orob2b_price_list_to_product o6_ ON (o6_.product_id = o0_.id)
        WHERE
        o2_.currency IN ('EUR', 'USD') AND
        o1_.unit_code IS NOT NULL AND
        o2_.quantity + 5 >= 0 AND
        o2_.value * 10 >= 0 AND
        o3_.id = 2 AND
        o6_.price_list_id = 4 AND
        o0_.id = 1;
         */

        $qb = $this->getQueryBuilder($priceRule);
        $q = $qb->getQuery()->getSQL();
        $actual = $this->getActualResult($qb);
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
        $unitLitre = $this->getReference('product_unit.liter');

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
