<?php

namespace OroB2B\Bundle\AccountBundle\Owner;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

use Oro\Component\DoctrineUtils\ORM\QueryUtils;
use Oro\Bundle\SecurityBundle\Owner\AbstractOwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class FrontendOwnerTreeProvider extends AbstractOwnerTreeProvider
{
    /**
     * @var CacheProvider
     */
    private $cache;

    /**
     * @var FrontendOwnershipMetadataProvider
     */
    private $ownershipMetadataProvider;

    /**
     * {@inheritDoc}
     */
    public function supports()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser() instanceof AccountUser;
    }

    /**
     * {@inheritdoc}
     */
    public function getCache()
    {
        if (!$this->cache) {
            $this->cache = $this->getContainer()->get('orob2b_account.owner.frontend_ownership_tree_provider.cache');
        }

        return $this->cache;
    }

    /**
     * {@inheritdoc}
     */
    protected function getOwnershipMetadataProvider()
    {
        if (!$this->ownershipMetadataProvider) {
            $this->ownershipMetadataProvider = $this->getContainer()
                ->get('orob2b_account.owner.frontend_ownership_metadata_provider');
        }

        return $this->ownershipMetadataProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function fillTree(OwnerTreeInterface $tree)
    {
        $ownershipMetadataProvider = $this->getOwnershipMetadataProvider();
        $accountUserClass = $ownershipMetadataProvider->getBasicLevelClass();
        $accountClass = $ownershipMetadataProvider->getLocalLevelClass();
        $connection = $this->getManagerForClass($accountUserClass)->getConnection();

        list($accounts, $columnMap) = $this->executeQuery(
            $connection,
            $this
                ->getRepository($accountClass)
                ->createQueryBuilder('a')
                ->select(
                    'a.id, IDENTITY(a.organization) orgId, IDENTITY(a.parent) parentId'
                    . ', (CASE WHEN a.parent IS NULL THEN 0 ELSE 1 END) AS HIDDEN ORD'
                )
                ->addOrderBy('ORD, parentId', 'ASC')
                ->getQuery()
        );
        foreach ($accounts as $account) {
            $orgId = $this->getId($account, $columnMap['orgId']);
            if (null !== $orgId) {
                $buId = $this->getId($account, $columnMap['id']);
                $tree->addLocalEntity($buId, $orgId);
                $tree->addDeepEntity($buId, $this->getId($account, $columnMap['parentId']));
            }
        }

        $tree->buildTree();

        list($accountUsers, $columnMap) = $this->executeQuery(
            $connection,
            $this
                ->getRepository($accountUserClass)
                ->createQueryBuilder('au')
                ->innerJoin('au.organizations', 'organizations')
                ->select(
                    'au.id as userId, organizations.id as orgId, IDENTITY(au.account) as accountId'
                )
                ->addOrderBy('orgId')
                ->getQuery()
        );
        $lastUserId = false;
        $lastOrgId = false;
        $processedUsers = [];
        foreach ($accountUsers as $accountUser) {
            $userId = $this->getId($accountUser, $columnMap['userId']);
            $orgId = $this->getId($accountUser, $columnMap['orgId']);
            $accountId = $this->getId($accountUser, $columnMap['accountId']);
            if ($userId !== $lastUserId && !isset($processedUsers[$userId])) {
                $tree->addBasicEntity($userId, $accountId);
                $processedUsers[$userId] = true;
            }
            if ($orgId !== $lastOrgId || $userId !== $lastUserId) {
                $tree->addGlobalEntity($userId, $orgId);
            }
            if (null !== $accountId) {
                $tree->addLocalEntityToBasic($userId, $accountId, $orgId);
            }
            $lastUserId = $userId;
            $lastOrgId = $orgId;
        }
    }

    /**
     * @param array  $item
     * @param string $property
     *
     * @return int|null
     */
    protected function getId($item, $property)
    {
        $id = $item[$property];

        return null !== $id ? (int)$id : null;
    }

    /**
     * @param Connection $connection
     * @param Query      $query
     *
     * @return array [rows, columnMap]
     */
    protected function executeQuery(Connection $connection, Query $query)
    {
        $parsedQuery = QueryUtils::parseQuery($query);

        return [
            $connection->executeQuery(QueryUtils::getExecutableSql($query, $parsedQuery)),
            array_flip($parsedQuery->getResultSetMapping()->scalarMappings)
        ];
    }

    /**
     * @param string $entityClass
     *
     * @return EntityRepository
     */
    protected function getRepository($entityClass)
    {
        return $this->getManagerForClass($entityClass)->getRepository($entityClass);
    }
}
