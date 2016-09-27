<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Driver;

use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\OrmIndexer;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;

/**
 * @dbIsolationPerTest
 */
class OrmAccountPartialUpdateDriverTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadProductVisibilityData::class]);

        $this->getContainer()->get('oro_account.visibility.cache.product.cache_builder')->buildCache();
    }

    public function testDeleteAccountVisibility()
    {
        /** @var OrmIndexer $indexer */
        $indexer = $this->getContainer()->get('oro_website_search.indexer');

        $indexer->reindex(Product::class);

        $this->assertProductAccountVisibilities(
            $this->getReference('product.2')->getId(),
            [
                $this->getReference('account.level_1')->getId(),
            ]
        );

        $this->assertProductAccountVisibilities(
            $this->getReference('product.5')->getId(),
            [
                $this->getReference('account.level_1')->getId(),
            ]
        );

        $this->assertProductAccountVisibilities(
            $this->getReference('product.5')->getId(),
            [
                $this->getReference('account.level_1.3')->getId(),
            ]
        );
    }

    /**
     * @param int $productId
     * @param array $accountIds
     */
    private function assertProductAccountVisibilities($productId, array $accountIds)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var WebsiteSearchIndexRepository $itemRepository */
        $itemRepository = $doctrine
            ->getManagerForClass(Item::class)
            ->getRepository(Item::class);

        $accountFields = [];
        foreach ($accountIds as $accountId) {
            $accountFields[] = 'visibility_account_' . $accountId;
        }

        $queryBuilder = $itemRepository->createQueryBuilder('item');
        $rowsCount = $queryBuilder
            ->select('COUNT(integerFields)')
            ->join('item.integerFields', 'integerFields')
            ->andWhere($queryBuilder->expr()->eq('item.entity', ':entityClass'))
            ->andWhere($queryBuilder->expr()->like('integerFields.field', ':accountFields'))
            ->andWhere($queryBuilder->expr()->eq('item.recordId', ':productId'))
            ->setParameters([
                'entityClass' => Product::class,
                'productId' => $productId,
                'accountFields' => $accountFields
            ])
            ->getQuery()
            ->getSingleScalarResult();

        $this->assertEquals(count($accountIds), $rowsCount);
    }
}
