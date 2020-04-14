<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\DBAL\Logging\DebugStack;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class PriceRuleLexemeRepositoryTest extends WebTestCase
{
    /** @var PriceRuleLexemeRepository */
    private $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadPriceRuleLexemes::class]);

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository(PriceRuleLexeme::class);
    }

    protected function tearDown(): void
    {
        $this->repository->invalidateCache();
    }

    /**
     * @param PriceRuleLexeme[] $lexemes
     *
     * @return array
     */
    private function getLexemeIds($lexemes)
    {
        $lexemeIds = array_map(
            function (PriceRuleLexeme $lexeme) {
                return $lexeme->getId();
            },
            $lexemes
        );

        sort($lexemeIds);
        return $lexemeIds;
    }

    public function testGetRelationIds()
    {
        /** @var PriceList $priceList1 */
        $priceList1 = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        /** @var PriceList $priceList2 */
        $priceList2 = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $relationIds = $this->repository->getRelationIds();
        sort($relationIds);
        $expected = [$priceList1->getId(), $priceList2->getId()];
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
        $expected = [
            $this->getReference('price_list_1_lexeme_1')->getId()
        ];
        $this->assertEquals($expected, $this->getLexemeIds($lexemes));
    }

    public function testFindEntityLexemesByClassNameAndFields()
    {
        $lexemes = $this->repository->findEntityLexemes(Category::class, ['id']);
        $expected = [
            $this->getReference('price_list_1_lexeme_1')->getId()
        ];
        $this->assertEquals($expected, $this->getLexemeIds($lexemes));
    }

    public function testFindEntityLexemesByClassNameAndRelationId()
    {
        $relationEntity = $this->getReference('price_list_1_lexeme_3');
        $lexemes = $this->repository->findEntityLexemes(
            PriceAttributeProductPrice::class,
            [],
            $relationEntity->getRelationId()
        );
        $expected = [
            $this->getReference('price_list_1_lexeme_3')->getId()
        ];
        $this->assertEquals($expected, $this->getLexemeIds($lexemes));
    }

    public function testFindEntityLexemesByClassNameAndFieldsAndRelationId()
    {
        $relationEntity = $this->getReference('price_list_1_lexeme_3');
        $lexemes = $this->repository->findEntityLexemes(
            PriceAttributeProductPrice::class,
            ['value'],
            $relationEntity->getRelationId()
        );
        $expected = [
            $this->getReference('price_list_1_lexeme_3')->getId()
        ];
        $this->assertEquals($expected, $this->getLexemeIds($lexemes));
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
