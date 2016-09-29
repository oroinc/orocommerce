<?php

namespace Oro\Bundle\WebsiteSearchBundle\Driver;

use Doctrine\ORM\Query as OrmQuery;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\EventListener\WebsiteSearchProductVisibilityIndexerListener;
use Oro\Bundle\AccountBundle\Visibility\Provider\AccountProductVisibilityProvider;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;

class OrmAccountPartialUpdateDriver implements AccountPartialUpdateDriverInterface
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    private $insertExecutor;

    /**
     * @var AccountProductVisibilityProvider
     */
    private $accountProductVisibilityProvider;

    /**
     * @var AbstractSearchMappingProvider
     */
    private $mappingProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param InsertFromSelectQueryExecutor $insertExecutor
     * @param AccountProductVisibilityProvider $accountProductVisibilityProvider
     * @param AbstractSearchMappingProvider $mappingProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        InsertFromSelectQueryExecutor $insertExecutor,
        AccountProductVisibilityProvider $accountProductVisibilityProvider,
        AbstractSearchMappingProvider $mappingProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->insertExecutor = $insertExecutor;
        $this->accountProductVisibilityProvider = $accountProductVisibilityProvider;
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function createAccountWithoutAccountGroupVisibility(Account $account)
    {
        $queryBuilder = $this->getIndexIntegerQueryBuilder('visibilityNew');

        $queryBuilder
            ->select('IDENTITY(visibilityNew.item) as itemId', 'visibilityNew.field', 'visibilityNew.value')
            ->join('visibilityNew.item', 'item')
            ->join(
                'item.integerFields',
                'isVisibleByDefault',
                OrmQuery\Expr\Join::WITH,
                'isVisibleByDefault.field = :isVisibleByDefaultField'
            )
            ->where($queryBuilder->expr()->eq('visibilityNew.field', ':visibilityNewField'))
            ->andWhere(
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
        $this->deleteAccountVisibility($account);

        /** @var WebsiteRepository $websiteRepository */
        $websiteRepository = $this->doctrineHelper->getEntityRepository(Website::class);
        $websites = $websiteRepository->getAllWebsites();
        foreach ($websites as $website) {
            $this->updateAccountVisibilityForWebsite($account, $website);
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
            ->accountProductVisibilityProvider
            ->getAccountProductsVisibilitiesByWebsiteQueryBuilder(
                $account,
                $website
            );

        $iterator = new BufferedQueryResultIterator($queryBuilder);
        $iterator->setHydrationMode(OrmQuery::HYDRATE_ARRAY);
        $batchSize = BufferedQueryResultIterator::DEFAULT_BUFFER_SIZE;

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
    private function insertAccountVisibilityData(Account $account, Website $website, array $productIds)
    {
        $queryBuilder = $this->getItemQueryBuilder();
        $queryBuilder
            ->select(
                'id',
                '"' . $this->getAccountVisibilityFieldName($account) . '"',
                WebsiteSearchProductVisibilityIndexerListener::ACCOUNT_VISIBILITY_VALUE
            )
            ->andWhere($queryBuilder->expr()->eq('entity', ':entityClass'))
            ->andWhere($queryBuilder->expr()->eq('alias', ':entityAlias'))
            ->andWhere($queryBuilder->expr()->in('recordId', ':recordIds'));

        //TODO: Refactor to use chained placeholder after 4035 fixes will be applied
        $entityAlias = $this->mappingProvider->getEntityAlias(Product::class);
        $entityAlias = str_replace('WEBSITE_ID', $website->getId(), $entityAlias);

        $queryBuilder
            ->setParameter('entityClass', Product::class)
            ->setParameter('alias', $entityAlias)
            ->setParameter('recordIds', $productIds);

        $this->insertExecutor->execute(IndexInteger::class, ['item_id', 'field', 'value'], $queryBuilder);
    }

    /**
     * @return string
     */
    private function getVisibilityNewFieldName()
    {
        return 'visibility_new';
    }

    /**
     * @return string
     */
    private function getIsVisibleByDefaultFieldName()
    {
        return 'is_visible_by_default';
    }

    /**
     * @param Account $account
     * @return string
     */
    private function getAccountVisibilityFieldName(Account $account)
    {
        return sprintf('visibility_account_%s', $account->getId());
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
     * @return \Doctrine\ORM\EntityManager|null
     */
    private function getEntityManager()
    {
        return $this->doctrineHelper->getEntityManagerForClass(Item::class);
    }
}
