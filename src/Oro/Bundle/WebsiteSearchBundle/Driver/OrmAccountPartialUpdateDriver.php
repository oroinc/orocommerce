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

        $accountVisibilityFieldName = 'visibility_account_' . $account->getId();

        $queryBuilder
            ->select(
                'IDENTITY(visibilityNew.item) as itemId',
                'CONCAT(\'visibility_account_\', :accountId)',
                'visibilityNew.value'
            )
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
            ->setParameter('accountId', $account->getId())
            ->setParameter('visibilityNewField', $this->getVisibilityNewFieldName())
            ->setParameter('isVisibleByDefaultField', $this->getIsVisibleByDefaultFieldName());

        $result = $queryBuilder->getQuery()->getResult();

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
            ->accountProductVisibilityProvider
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
        //TODO: Refactor to use chained placeholder after 4035 fixes will be applied
        $entityAlias = $this->mappingProvider->getEntityAlias(Product::class);
        $entityAlias = str_replace('WEBSITE_ID', $website->getId(), $entityAlias);

        $queryBuilder = $this->getItemQueryBuilder();
        $queryBuilder
            ->select(
                'searchItem.id',
                '\'' . $this->getAccountVisibilityFieldName($account) . '\'',
                '\'' . WebsiteSearchProductVisibilityIndexerListener::ACCOUNT_VISIBILITY_VALUE . '\''
            )
            ->andWhere($queryBuilder->expr()->eq('searchItem.entity', ':entityClass'))
            ->andWhere($queryBuilder->expr()->eq('searchItem.alias', ':entityAlias'))
            ->andWhere($queryBuilder->expr()->in('searchItem.recordId', ':productIds'))
            ->setParameter('entityClass', Product::class)
            ->setParameter('entityAlias', $entityAlias)
            ->setParameter('productIds', $productIds);

        $this->insertExecutor->execute(IndexInteger::class, ['item', 'field', 'value'], $queryBuilder);
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
