<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\DBAL\Logging\DebugStack;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class PriceRuleLexemeRepositoryTest extends WebTestCase
{
    /** @var PriceRuleLexemeRepository */
    private $repository;
    private $defaultPriceListId;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadPriceRuleLexemes::class]);

        $doctrine = $this->getContainer()->get('doctrine');

        $this->defaultPriceListId = $doctrine
            ->getRepository(PriceList::class)
            ->findOneBy(['name' => 'Default Price List'])
            ->getId();
        $this->repository = $doctrine->getRepository(PriceRuleLexeme::class);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->repository->invalidateCache();
    }

    public function testGetRelationIds()
    {
        $relationIds = $this->repository->getRelationIds();
        sort($relationIds);
        $expected = [$this->defaultPriceListId];
        $this->assertEquals($expected, $relationIds);
    }

    /**
     * @depends testGetRelationIds
     */
    public function testDeleteByPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        // Check existing lexemes for price list
        $lexemes = $this->repository->findBy(['priceList' => $priceList]);
        $this->assertNotEmpty($lexemes);

        $this->repository->deleteByPriceList($priceList);

        // Check Lexemes for price list are empty
        $lexemes = $this->repository->findBy(['priceList' => $priceList]);
        $this->assertEmpty($lexemes);
    }

    public function testFindEntityLexemesByClassName()
    {
        $lexemes = $this->repository->findEntityLexemes(Category::class);
        // 5 rule lexemes from PriceRuleLexeme + 2 productAssignmentRule from LoadPriceLists
        $this->assertCount(7, $lexemes);
        foreach ($lexemes as $lexeme) {
            $this->assertEquals(Category::class, $lexeme->getClassName());
        }
    }

    public function testFindEntityLexemesByClassNameAndFields()
    {
        $lexemes = $this->repository->findEntityLexemes(Product::class, ['status']);
        $this->assertCount(1, $lexemes);
        foreach ($lexemes as $lexeme) {
            $this->assertEquals(Product::class, $lexeme->getClassName());
            $this->assertEquals('status', $lexeme->getFieldName());
        }
    }

    public function testFindEntityLexemesByClassNameAndRelationId()
    {
        $lexemes = $this->repository->findEntityLexemes(
            ProductPrice::class,
            [],
            $this->defaultPriceListId
        );
        $this->assertCount(5, $lexemes);
        foreach ($lexemes as $lexeme) {
            $this->assertEquals(ProductPrice::class, $lexeme->getClassName());
            $this->assertEquals($this->defaultPriceListId, $lexeme->getRelationId());
        }
    }

    public function testFindEntityLexemesByClassNameAndFieldsAndRelationId()
    {
        $lexemes = $this->repository->findEntityLexemes(
            ProductPrice::class,
            ['value'],
            $this->defaultPriceListId
        );
        $this->assertCount(5, $lexemes);
        foreach ($lexemes as $lexeme) {
            $this->assertEquals(ProductPrice::class, $lexeme->getClassName());
            $this->assertEquals('value', $lexeme->getFieldName());
            $this->assertEquals($this->defaultPriceListId, $lexeme->getRelationId());
        }
    }

    public function testFindEntityLexemesByClassNameAndNotUsedFields()
    {
        $lexemes = $this->repository->findEntityLexemes(Category::class, ['some_other_unknown']);
        $this->assertEmpty($lexemes);
    }

    public function testInvalidateCache()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(PriceRuleLexeme::class);

        $config = $em->getConnection()->getConfiguration();
        $oldLogger = $config->getSQLLogger();

        $logger = new DebugStack();
        $config->setSQLLogger($logger);

        $this->repository->findEntityLexemes(PriceAttributeProductPrice::class);
        $this->assertCount(1, $logger->queries);

        $this->repository->findEntityLexemes(PriceAttributeProductPrice::class);
        $this->assertCount(1, $logger->queries);

        $this->repository->findEntityLexemes(Category::class);
        $this->assertCount(2, $logger->queries);

        $this->repository->findEntityLexemes(Category::class);
        $this->assertCount(2, $logger->queries);

        $this->repository->invalidateCache();

        $this->repository->findEntityLexemes(PriceAttributeProductPrice::class);
        $this->assertCount(3, $logger->queries);

        $this->repository->findEntityLexemes(Category::class);
        $this->assertCount(4, $logger->queries);

        $config->setSQLLogger($oldLogger);
    }
}
