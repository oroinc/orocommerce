<?php

namespace Oro\Bundle\CustomerBundle\Owner;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Component\DoctrineUtils\ORM\QueryUtils;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;
use Oro\Bundle\SecurityBundle\Owner\AbstractOwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class FrontendOwnerTreeProvider extends AbstractOwnerTreeProvider
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var FrontendOwnershipMetadataProvider */
    private $ownershipMetadataProvider;

    /**
     * @param ManagerRegistry           $doctrine
     * @param DatabaseChecker           $databaseChecker
     * @param CacheProvider             $cache
     * @param MetadataProviderInterface $ownershipMetadataProvider
     * @param TokenStorageInterface     $tokenStorage
     */
    public function __construct(
        ManagerRegistry $doctrine,
        DatabaseChecker $databaseChecker,
        CacheProvider $cache,
        MetadataProviderInterface $ownershipMetadataProvider,
        TokenStorageInterface $tokenStorage
    ) {
        parent::__construct($databaseChecker, $cache);
        $this->doctrine = $doctrine;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritDoc}
     */
    public function supports()
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return false;
        }

        return $token->getUser() instanceof CustomerUser;
    }

    /**
     * {@inheritdoc}
     */
    protected function fillTree(OwnerTreeInterface $tree)
    {
        $ownershipMetadataProvider = $this->getOwnershipMetadataProvider();
        $customerUserClass = $ownershipMetadataProvider->getBasicLevelClass();
        $customerClass = $ownershipMetadataProvider->getLocalLevelClass();
        $connection = $this->getManagerForClass($customerUserClass)->getConnection();

        list($customers, $columnMap) = $this->executeQuery(
            $connection,
            $this
                ->getRepository($customerClass)
                ->createQueryBuilder('a')
                ->select(
                    'a.id, IDENTITY(a.organization) orgId, IDENTITY(a.parent) parentId'
                    . ', (CASE WHEN a.parent IS NULL THEN 0 ELSE 1 END) AS HIDDEN ORD'
                )
                ->addOrderBy('ORD, parentId', 'ASC')
                ->getQuery()
        );
        foreach ($customers as $customer) {
            $orgId = $this->getId($customer, $columnMap['orgId']);
            if (null !== $orgId) {
                $buId = $this->getId($customer, $columnMap['id']);
                $tree->addLocalEntity($buId, $orgId);
                $tree->addDeepEntity($buId, $this->getId($customer, $columnMap['parentId']));
            }
        }

        $tree->buildTree();

        list($customerUsers, $columnMap) = $this->executeQuery(
            $connection,
            $this
                ->getRepository($customerUserClass)
                ->createQueryBuilder('au')
                ->select(
                    'au.id as userId, IDENTITY(au.organization) as orgId, IDENTITY(au.customer) as customerId'
                )
                ->addOrderBy('orgId')
                ->getQuery()
        );
        $lastUserId = false;
        $lastOrgId = false;
        $processedUsers = [];
        foreach ($customerUsers as $customerUser) {
            $userId = $this->getId($customerUser, $columnMap['userId']);
            $orgId = $this->getId($customerUser, $columnMap['orgId']);
            $customerId = $this->getId($customerUser, $columnMap['customerId']);
            if ($userId !== $lastUserId && !isset($processedUsers[$userId])) {
                $tree->addBasicEntity($userId, $customerId);
                $processedUsers[$userId] = true;
            }
            if ($orgId !== $lastOrgId || $userId !== $lastUserId) {
                $tree->addGlobalEntity($userId, $orgId);
            }
            if (null !== $customerId) {
                $tree->addLocalEntityToBasic($userId, $customerId, $orgId);
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
     * @param string $className
     *
     * @return EntityManager
     */
    protected function getManagerForClass($className)
    {
        return $this->doctrine->getManagerForClass($className);
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

    /**
     * @return MetadataProviderInterface
     */
    protected function getOwnershipMetadataProvider()
    {
        return $this->ownershipMetadataProvider;
    }
}
