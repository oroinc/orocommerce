<?php

namespace Oro\Bundle\CustomerBundle\Driver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Visibility\Provider\ProductVisibilityProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Provider\PlaceholderProvider;

class OrmAccountPartialUpdateDriver extends AbstractAccountPartialUpdateDriver
{
    /**
     * @var InsertFromSelectQueryExecutor
     */
    private $insertExecutor;

    /**
     * @var EntityManagerInterface
     */
    private $itemEntityManager;

    /**
     * @param PlaceholderProvider $placeholderProvider
     * @param DoctrineHelper $doctrineHelper
     * @param InsertFromSelectQueryExecutor $insertExecutor
     * @param ProductVisibilityProvider $productVisibilityProvider
     */
    public function __construct(
        PlaceholderProvider $placeholderProvider,
        DoctrineHelper $doctrineHelper,
        InsertFromSelectQueryExecutor $insertExecutor,
        ProductVisibilityProvider $productVisibilityProvider
    ) {
        parent::__construct($placeholderProvider, $productVisibilityProvider, $doctrineHelper);

        $this->insertExecutor = $insertExecutor;
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
                Query\Expr\Join::WITH,
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
            ->getEntityManagerForClass(Item::class)
            ->getConnection();

        $connection->beginTransaction();

        try {
            parent::updateAccountVisibility($account);

            $connection->commit();
        } catch (\Exception $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addAccountVisibility(
        array $productIds,
        $productAlias,
        $accountVisibilityFieldName,
        $fieldValue
    ) {
        $queryBuilder = $this->getItemQueryBuilder();
        $queryBuilder
            ->select(
                'searchItem.id',
                sprintf("'%s'", $accountVisibilityFieldName),
                (string)$fieldValue
            )
            ->andWhere($queryBuilder->expr()->eq('searchItem.entity', ':entityClass'))
            ->andWhere($queryBuilder->expr()->eq('searchItem.alias', ':entityAlias'))
            ->andWhere($queryBuilder->expr()->in('searchItem.recordId', ':productIds'))
            ->setParameter('entityClass', Product::class)
            ->setParameter('entityAlias', $productAlias)
            ->setParameter('productIds', $productIds);

        $this->insertExecutor->execute(IndexInteger::class, ['item', 'field', 'value'], $queryBuilder);
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
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        if (!$this->itemEntityManager) {
            $this->itemEntityManager = $this->doctrineHelper->getEntityManagerForClass(Item::class);
        }

        return $this->itemEntityManager;
    }
}
