<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Compiler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use OroB2B\Bundle\PricingBundle\Compiler\ProductAssignmentRuleCompiler;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributeProductPrices;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
class ProductAssignmentRuleCompilerTest extends WebTestCase
{
    /**
     * @var ProductAssignmentRuleCompiler
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
        $this->compiler = $this->getContainer()->get('orob2b_pricing.compiler.product_assignment_rule_compiler');
    }

    public function testCompileTwoProducts()
    {
        /** @var Category $category1 */
        $category1 = $this->getReference('category_1');
        /** @var Category $category2 */
        $category2 = $this->getReference('category_1_2');
        $assignmentRule = '(product.category == ' . $category1->getId() . ' or category == ' . $category2->getId() . ')'
            . " and (
                    product.price_attribute_price_list_1.value > 1
                    or product.price_attribute_price_list_2.currency == 'USD'
                )
            ";

        $priceList = $this->createPriceList($assignmentRule);
        $qb = $this->getQueryBuilder($priceList);

        /** @var Product $product1 */
        $product1 = $this->getReference('product.1');
        /** @var Product $product2 */
        $product2 = $this->getReference('product.2');
        $expected = [
            [$product1->getId(), $priceList->getId(), false],
            [$product2->getId(), $priceList->getId(), false],
        ];
        $actual = $this->getActualResult($qb);
        $this->assertEquals($expected, $actual);
    }

    public function testCompileWithManuallyAssigned()
    {
        /** @var Category $category1 */
        $category1 = $this->getReference('category_1');
        $assignmentRule = 'product.category == ' . $category1->getId()
            . " and (
                    product.price_attribute_price_list_1.value > 1
                    or product.price_attribute_price_list_2.currency == 'USD'
                )
            ";

        $priceList = $this->createPriceList($assignmentRule);
        $qb = $this->getQueryBuilder($priceList);

        /** @var Product $product1 */
        $product1 = $this->getReference('product.1');
        /** @var Product $product2 */
        $product2 = $this->getReference('product.2');
        $expected = [
            [$product1->getId(), $priceList->getId(), false],
            [$product2->getId(), $priceList->getId(), false],
        ];
        $actual = $this->getActualResult($qb);
        $this->assertEquals($expected, array_values($actual));
    }

    /**
     * @param string $assignmentRule
     * @return PriceList
     */
    protected function createPriceList($assignmentRule)
    {
        $priceList = new PriceList();
        $priceList->setActive(true)
            ->setCurrencies(['USD', 'EUR'])
            ->setName('Test Assignment Rules Price List')
            ->setProductAssignmentRule($assignmentRule);
        $em = $this->registry->getManagerForClass(PriceList::class);
        $em->persist($priceList);
        $em->flush();

        return $priceList;
    }

    /**
     * @param PriceList $priceList
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getQueryBuilder($priceList)
    {
        $qb = $this->compiler->compile($priceList);
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
}
