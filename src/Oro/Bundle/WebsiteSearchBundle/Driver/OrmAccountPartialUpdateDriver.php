<?php

namespace Oro\Bundle\WebsiteSearchBundle\Driver;

use Doctrine\ORM\Query as OrmQuery;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\AccountBundle\Visibility\Provider\ProductVisibilityProvider;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\VisitorReplacePlaceholder;

class OrmAccountPartialUpdateDriver extends AbstractAccountPartialUpdateDriver
{
    const PRODUCT_SELECT_FOR_UPDATE_BATCH_SIZE = 100000;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    private $insertExecutor;

    /**
     * @var ProductVisibilityProvider
     */
    private $productVisibilityProvider;

    /**
     * @param VisitorReplacePlaceholder $visitorReplacePlaceholder
     * @param AbstractSearchMappingProvider $mappingProvider
     * @param DoctrineHelper $doctrineHelper
     * @param InsertFromSelectQueryExecutor $insertExecutor
     * @param ProductVisibilityProvider $productVisibilityProvider
     */
    public function __construct(
        VisitorReplacePlaceholder $visitorReplacePlaceholder,
        AbstractSearchMappingProvider $mappingProvider,
        DoctrineHelper $doctrineHelper,
        InsertFromSelectQueryExecutor $insertExecutor,
        ProductVisibilityProvider $productVisibilityProvider
    ) {
        parent::__construct($visitorReplacePlaceholder, $mappingProvider);

        $this->doctrineHelper = $doctrineHelper;
        $this->insertExecutor = $insertExecutor;
        $this->productVisibilityProvider = $productVisibilityProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function createAccountWithoutAccountGroupVisibility(Account $account)
    {
        $queryBuilder = $this->getIndexIntegerQueryBuilder('visibilityNew');

        $queryBuilder
            ->select(
                'IDENTITY(visibilityNew.item) as itemId',
                sprintf("'%s'", $this->getAccountVisibilityFieldName($account)),
                'visibilityNew.value'
            )
            ->join('visibilityNew.item', 'item')
            ->join(
                'item.integerFields',
                'isVisibleByDefault',
                OrmQuery\Expr\Join::WITH,
                'isVisibleByDefault.field = :isVisibleByDefaultField'
            )
            ->andWhere(
                $queryBuilder->expr()->eq('visibilityNew.field', ':visibilityNewField'),
                $queryBuilder->expr()->eq('item.entity', ':entityClass'),
                $queryBuilder->expr()->neq('visibilityNew.value', 'isVisibleByDefault.value')
            )
            ->setParameter('entityClass', Product::class)
            ->setParameter('visibilityNewField', $this->getVisibilityNewFieldName())
            ->setParameter('isVisibleByDefaultField', $this->getIsVisibleByDefaultFieldName());

        $this->insertExecutor->execute(IndexInteger::class, ['item', 'field', 'value'], $queryBuilder);
    }

    /**
     * {@inheritdoc}
     */
    public function updateAccountVisibility(Account $account)
    {
        $connection = $this
            ->doctrineHelper
            ->getEntityManagerForClass(Item::class);

        $connection->beginTransaction();

        try {
            $this->deleteAccountVisibility($account);

            /** @var WebsiteRepository $websiteRepository */
            $websiteRepository = $this->doctrineHelper->getEntityRepository(Website::class);
            $websites = $websiteRepository->getAllWebsites();
            foreach ($websites as $website) {
                $this->updateAccountVisibilityForWebsite($account, $website);
            }

            $connection->commit();
        } catch (\Exception $exception) {
            $connection->rollback();
            throw $exception;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAccountVisibility(Account $account)
    {
        $itemQueryBuilder = $this->getItemQueryBuilder();

        $queryBuilder = $this->getIndexIntegerQueryBuilder('indexInteger');

        $queryBuilder
            ->delete()
            ->where($queryBuilder->expr()->in('indexInteger.item', $itemQueryBuilder->getDQL()))
            ->andWhere($queryBuilder->expr()->eq('indexInteger.field', ':fieldName'))
            ->setParameters($itemQueryBuilder->getParameters())
            ->setParameter('fieldName', $this->getAccountVisibilityFieldName($account))
            ->getQuery()
            ->execute();
    }

    /**
     * @param Account $account
     * @param Website $website
     */
    private function updateAccountVisibilityForWebsite(Account $account, Website $website)
    {
        $queryBuilder = $this
            ->productVisibilityProvider
            ->getAccountProductsVisibilitiesByWebsiteQueryBuilder(
                $account,
                $website
            );

        $queryBuilder->select('product.id');

        $batchSize = self::PRODUCT_SELECT_FOR_UPDATE_BATCH_SIZE;
        $iterator = new BufferedQueryResultIterator($queryBuilder);
        $iterator->setHydrationMode(OrmQuery::HYDRATE_ARRAY);
        $iterator->setBufferSize($batchSize);

        $productIds = [];
        $rows = 0;
        foreach ($iterator as $index => $productData) {
            ++$rows;
            $productIds[] = $productData['id'];

            if ($rows % $batchSize === 0) {
                $this->insertAccountVisibilityData($account, $website, $productIds);
                $productIds = [];
            }
        }

        if ($productIds !== []) {
            $this->insertAccountVisibilityData($account, $website, $productIds);
        }
    }

    /**
     * @param Account $account
     * @param Website $website
     * @param array $productIds
     */
    protected function insertAccountVisibilityData(Account $account, Website $website, array $productIds)
    {
        $queryBuilder = $this->getItemQueryBuilder();
        $queryBuilder
            ->select(
                'searchItem.id',
                sprintf("'%s'", $this->getAccountVisibilityFieldName($account)),
                (string)ProductVisibilityIndexer::ACCOUNT_VISIBILITY_VALUE
            )
            ->andWhere($queryBuilder->expr()->eq('searchItem.entity', ':entityClass'))
            ->andWhere($queryBuilder->expr()->eq('searchItem.alias', ':entityAlias'))
            ->andWhere($queryBuilder->expr()->in('searchItem.recordId', ':productIds'))
            ->setParameter('entityClass', Product::class)
            ->setParameter('entityAlias', $this->getProductAliasByWebsite($website))
            ->setParameter('productIds', $productIds);

        $this->insertExecutor->execute(IndexInteger::class, ['item', 'field', 'value'], $queryBuilder);
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getItemQueryBuilder()
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder
            ->select('searchItem')
            ->from(Item::class, 'searchItem')
            ->where($queryBuilder->expr()->eq('searchItem.entity', ':entityClass'))
            ->setParameter('entityClass', Product::class);

        return $queryBuilder;
    }

    /**
     * @param string $alias
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getIndexIntegerQueryBuilder($alias)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->from(IndexInteger::class, $alias);
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager()
    {
        try {
            $manager = $this->doctrineHelper->getEntityManagerForClass(Item::class);
        } catch (NotManageableEntityException $exception) {
        }

        return $manager;
    }
}
