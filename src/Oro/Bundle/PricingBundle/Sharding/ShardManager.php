<?php

namespace Oro\Bundle\PricingBundle\Sharding;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Constraint;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;

/**
 * Manage shards for given class.
 * CRUD operation for shards tables, reorganize existing table to use shards.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShardManager
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var array
     */
    protected $shardMap = [];

    /**
     * @var array
     */
    protected $shardList = [];

    /**
     * @var bool
     */
    protected $enableSharding;

    public function __construct(array $shardList = [])
    {
        $this->shardList = $shardList;
    }

    /**
     * @param string $className
     * @param array $attributes
     * @return string
     * @throws \Exception
     */
    public function getEnabledShardName($className, array $attributes)
    {
        $baseTableName = $this->getEntityBaseTable($className);

        if (!$this->enableSharding) {
            return $baseTableName;
        }

        return $this->getShardName($className, $attributes);
    }

    /**
     * @param string $className
     * @param array $attributes
     * @return string
     * @throws \Oro\Bundle\PricingBundle\Sharding\EntityNotSupportsShardingException
     */
    public function getShardName($className, array $attributes)
    {
        if (!array_key_exists($className, $this->getShardMap())) {
            throw new EntityNotSupportsShardingException('Entity ' . $className . ' wasn\'t registered for sharding');
        }

        $baseTableName = $this->getEntityBaseTable($className);
        $discValue = $this->getDiscriminationValue($className, $attributes);

        return sprintf("%s_%s", $baseTableName, $discValue);
    }

    /**
     * @param string $className
     */
    public function moveDataFromBaseTableToShard($className)
    {
        $baseTableName = $this->getBaseTableName($className);
        $connection = $this->getConnection();

        foreach ($this->getShardsAttributes($className) as $attributes) {
            $shardName = $this->getShardName($className, $attributes);
            $discriminationValue = $this->getDiscriminationValue($className, $attributes);

            if (!$this->exists($shardName)) {
                $this->create($className, $shardName);
            }

            $column = $connection->getDatabasePlatform()->quoteIdentifier($this->getDiscriminationColumn($className));
            $columnsStr = $this->getColumnsPlaceholder($className);
            $sql = "INSERT INTO $shardName ($columnsStr) SELECT $columnsStr FROM $baseTableName WHERE $column = :value";
            $connection->executeStatement($sql, ["value" => $discriminationValue]);
        }
        $connection->executeStatement("DELETE FROM $baseTableName");
    }

    /**
     * @param string $class
     */
    public function moveDataFromShardsToBaseTable($class)
    {
        $connection = $this->getConnection();
        $baseTableName = $this->getBaseTableName($class);

        foreach ($this->getShardsAttributes($class) as $attributes) {
            $shardName = $this->getShardName($class, $attributes);
            if (!$this->exists($shardName)) {
                continue;
            }
            $columnsStr = $this->getColumnsPlaceholder($class);
            $sql = "INSERT INTO $baseTableName ($columnsStr) SELECT $columnsStr FROM $shardName";
            $connection->executeStatement($sql);
            $this->delete($shardName);
        }
    }

    /**
     * @param string $class
     * @return array
     */
    protected function getShardsAttributes($class)
    {
        $discFieldName = $this->getDiscriminationField($class);
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass($class);
        $metadata = $em->getClassMetadata($class);
        /** @var ClassMetadata $targetMetadata */
        $targetMetadata = $em->getClassMetadata($metadata->getAssociationTargetClass($discFieldName));
        $targetColumnName = $metadata->getSingleAssociationReferencedJoinColumnName($discFieldName);
        $targetFieldName = $targetMetadata->getFieldForColumn($targetColumnName);
        $qb = $em->getRepository($metadata->getAssociationTargetClass($discFieldName))->createQueryBuilder('target');

        $attributes = $qb->select("target.$targetFieldName as $discFieldName")->getQuery()->getArrayResult();

        return $attributes;
    }

    /**
     * @param string $class
     * @return string
     */
    protected function getColumnsPlaceholder($class)
    {
        $connection = $this->getConnection();
        $sm = $connection->getSchemaManager();

        $baseTableName = $this->getBaseTableName($class);
        /** @var Table $table */
        $table = $sm->listTableDetails($baseTableName);
        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[] = $column->getQuotedName($connection->getDatabasePlatform());
        }
        return implode(', ', $columns);
    }

    /**
     * @param string $class
     * @param array $attributes
     * @return mixed
     * @throws \Exception
     */
    protected function getDiscriminationValue($class, array $attributes)
    {
        $em = $this->registry->getManagerForClass($class);
        /** @var ClassMetadata $metadata */
        $metadata = $em->getClassMetadata($class);
        $discFieldName = $this->getDiscriminationField($class);
        if (!array_key_exists($discFieldName, $attributes)) {
            throw new \Exception(
                sprintf("Required attribute '%s' for generation of shard name missing.", $discFieldName)
            );
        }
        $discValue = $attributes[$discFieldName];
        if (!is_object($discValue)) {
            return $discValue;
        }
        $accessor = PropertyAccess::createPropertyAccessor();
        /** @var ClassMetadata $targetMetadata */
        $targetClassName = $metadata->getAssociationTargetClass($discFieldName);
        if (!is_a($discValue, $targetClassName)) {
            throw new \Exception(
                sprintf("Wrong type of '%s' to generate shard name.", $discFieldName)
            );
        }

        $targetMetadata = $em->getClassMetadata($targetClassName);
        $targetColumnName = $metadata->getSingleAssociationReferencedJoinColumnName($discFieldName);
        $targetFieldName = $targetMetadata->getFieldForColumn($targetColumnName);

        return $accessor->getValue($discValue, $targetFieldName);
    }

    /**
     * @param string $className
     * @return bool
     */
    public function isEntitySharded($className)
    {
        return in_array($className, $this->getShardList());
    }

    /**
     * @param string $className
     * @param string $shardName
     */
    public function create($className, $shardName)
    {
        $baseTableName = $this->getBaseTableName($className);
        $connection = $this->getConnection();

        $sm = $connection->getSchemaManager();

        /** @var Table $table */
        $table = $sm->listTableDetails($baseTableName);

        $search = [$baseTableName];
        $replace = [$shardName];
        foreach ($table->getIndexes() as $index) {
            if ($index->getName() === 'PRIMARY') {
                continue;
            }
            $search[] = $index->getName();
            $replace[] = $this->generateIdentifierName($shardName, $index);
        }
        foreach ($table->getForeignKeys() as $foreignKey) {
            $search[] = $foreignKey->getName();
            $replace[] = $this->generateIdentifierName($shardName, $foreignKey);
        }

        $createQueries = $this->getCreateTableQueries($table);
        foreach ($createQueries as $query) {
            $query = str_replace($search, $replace, $query);
            $connection->executeQuery($query);
        }
    }

    /**
     * @param string $shardName
     * @return bool
     */
    public function exists($shardName)
    {
        $connection = $this->getConnection();
        return $connection->getSchemaManager()->tablesExist([$shardName]);
    }

    /**
     * @param string $shardName
     */
    public function delete($shardName)
    {
        if ($this->enableSharding) {
            $this->getConnection()->getSchemaManager()->dropTable($shardName);
        }
    }

    /**
     * @param string $className
     * @return string
     */
    protected function getBaseTableName($className)
    {
        $em = $this->registry->getManagerForClass($className);

        /** @var ClassMetadata $metadata */
        $metadata = $em->getClassMetadata($className);
        $baseTableName = $metadata->getTableName();

        return $baseTableName;
    }

    /**
     * @return Connection
     */
    protected function getConnection()
    {
        return $this->getEntityManager()->getConnection();
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->registry->getManager();
    }
    /**
     * @param string $shardName
     * @param Constraint $asset
     * @return string
     */
    protected function generateIdentifierName($shardName, Constraint $asset)
    {
        $columnNames = array_merge([$shardName], $asset->getColumns());
        $hash = implode("", array_map(function ($column) {
            return dechex(crc32($column));
        }, $columnNames));

        $prefix = "";
        if ($asset instanceof Index) {
            $prefix = "idx";
        } elseif ($asset instanceof ForeignKeyConstraint) {
            $prefix = "fk";
        }

        return substr(strtoupper($prefix . "_" . $hash), 0, 30);
    }

    /**
     * @param string $alias
     * @param string $className
     */
    public function addEntityForShard($alias, $className)
    {
        $this->shardList[$alias] = $className;
        $this->shardMap = [];
    }

    /**
     * @param string $className
     *
     * @return string
     */
    public function getEntityBaseTable($className)
    {
        $metadata = $this->getEntityManager()->getClassMetadata($className);

        return $metadata->getTableName();
    }

    /**
     * @return array
     */
    public function getShardMap()
    {
        if (empty($this->shardMap) && !empty($this->shardList)) {
            foreach ($this->shardList as $className) {
                $this->shardMap[$className] = $this->getBaseTableName($className);
            }
        }

        return $this->shardMap;
    }

    /**
     * @param string $className
     * @return string
     */
    public function getDiscriminationField($className)
    {
        return $this->configProvider->getConfig($className)
            ->get('discrimination_field');
    }

    /**
     * @param string $className
     * @return string
     * @throws \Exception
     */
    public function getDiscriminationColumn($className)
    {
        $fieldName = $this->getDiscriminationField($className);
        $em = $this->registry->getManagerForClass($className);
        /** @var ClassMetadata $metadata */
        $metadata = $em->getClassMetadata($className);

        if (!$metadata->hasAssociation($fieldName)) {
            throw new \Exception(
                sprintf("Class '%s' has invalid '%s' discrimination field config", $className, $fieldName)
            );
        }

        return $metadata->getSingleAssociationJoinColumnName($fieldName);
    }

    public function __serialize(): array
    {
        return $this->shardList;
    }

    public function __unserialize(array $serialized): void
    {
        $this->shardList = $serialized;
    }

    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param boolean $enableSharding
     */
    public function setEnableSharding($enableSharding)
    {
        $this->enableSharding = $enableSharding;
    }

    /**
     * @param ConfigProvider $configProvider
     */
    public function setConfigProvider($configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @param Table $table
     * @return array
     */
    protected function getCreateTableQueries(Table $table)
    {
        $connection = $this->getConnection();
        $connection->getDriver();
        $createFlags = AbstractPlatform::CREATE_INDEXES | AbstractPlatform::CREATE_FOREIGNKEYS;
        $createQueries = $connection->getDatabasePlatform()->getCreateTableSQL($table, $createFlags);

        //create single create table query
        if ($connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $createQuery = $createQueries[0];
            $constraints = [];
            foreach ($createQueries as $query) {
                if (str_starts_with($query, 'ALTER TABLE')) {
                    $constraints[] = substr($query, strpos($query, 'CONSTRAINT'));
                }
            }
            $additionalSql = implode(', ', $constraints);
            $createQuery = str_replace('PRIMARY KEY(id)', 'PRIMARY KEY(id), ' . $additionalSql, $createQuery);
            $createQueries = [$createQuery];
        }

        return $createQueries;
    }

    /**
     * @return array
     */
    public function getShardList()
    {
        return $this->shardList;
    }

    /**
     * @return boolean
     */
    public function isShardingEnabled()
    {
        return $this->enableSharding;
    }
}
