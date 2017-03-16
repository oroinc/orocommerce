<?php

namespace Oro\Bundle\PricingBundle\Sharding;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Constraint;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ShardManager implements \Serializable
{
    /**
     * @var RegistryInterface
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
     * @param array $shardList
     */
    public function __construct(array $shardList = [])
    {
        $this->shardList = $shardList;
    }

    /**
     * @param $className
     * @param array $attributes
     * @return string
     * @throws \Exception
     */
    public function getShardName($className, array $attributes)
    {
        $baseTableName = $this->getEntityBaseTable($className);

        if (!isset($attributes['priceList'])) {
            throw new \Exception(sprintf("Required attribute '%s' for generation of shard name missing.", "priceList"));
        } elseif (is_a($attributes['priceList'], PriceList::class)) {
            /** @var PriceList $priceList */
            $priceList = $attributes['priceList'];
            $id = $priceList->getId();
        } elseif (is_int($attributes['priceList']) || is_string($attributes['priceList'])) {
            $id = $attributes['priceList'];
        } else {
            throw new \Exception(sprintf("Wrong type of '%s' to generate shard name.", "priceList"));
        }

        $shardName = sprintf("%s_%s", $baseTableName, $id);

        return $shardName;
    }

    /**
     * @param $className
     * @return bool
     */
    public function isEntitySharded($className)
    {
        return in_array($className, $this->getShardMap());
    }

    /**
     * @param string $className
     * @param string $shardName
     *
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
        foreach ($createQueries as $key => $query) {
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
        $this->getConnection()->getSchemaManager()->dropTable($shardName);
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
        /** @var EntityManager $em */
        $em = $this->registry->getManager();
        $connection = $em->getConnection();
        if ($connection->getDriver()->getName() === DatabaseDriverInterface::DRIVER_MYSQL) {
            $em = $this->registry->getManager('price');
            $connection = $em->getConnection();
        }

        return $connection;
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
     * @param $className
     */
    public function addEntityForShard($className)
    {
        $this->shardList[] = $className;
        $this->shardMap = [];
    }

    /**
     * @param $className
     * @return mixed
     * @throws \Exception
     */
    public function getEntityBaseTable($className)
    {
        if (!array_key_exists($className, $this->getShardMap())) {
            throw new \Exception('Entity ' . $className . ' wasn\'t registered for sharding');
        }

        return $this->getShardMap()[$className];
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
     * @param $className
     * @return string
     */
    public function getDiscriminationField($className)
    {
        return $this->configProvider->getConfig($className)
            ->get('discrimination_field');
    }

    /**
     * @param $className
     * @return string
     */
    public function getDiscriminationColumn($className)
    {
        $fieldName = $this->getDiscriminationField($className);
        $columnName = $fieldName;
        $em = $this->registry->getManagerForClass($className);
        /** @var ClassMetadata $metadata */
        $metadata = $em->getClassMetadata($className);
        if (isset($metadata->columnNames[$fieldName])) {
            $columnName = $metadata->columnNames[$fieldName];
        } elseif (isset($metadata->associationMappings[$fieldName])) {
            $columnName = $metadata->associationMappings[$fieldName]['joinColumns'][0]['name'];
        }

        return $columnName;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize($this->shardList);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $shardList = unserialize($serialized);
        $this->shardList = $shardList;
    }

    /**
     * @param RegistryInterface $registry
     */
    public function setRegistry(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ConfigProvider $configProvider
     */
    public function setConfigProvider($configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @param $table
     * @return array
     */
    protected function getCreateTableQueries($table)
    {
        $connection = $this->getConnection();
        $connection->getDriver();
        $createFlags = AbstractPlatform::CREATE_INDEXES | AbstractPlatform::CREATE_FOREIGNKEYS;
        $createQueries = $connection->getDatabasePlatform()->getCreateTableSQL($table, $createFlags);

        //create single create table query
        if ($connection->getDriver()->getName() === DatabaseDriverInterface::DRIVER_MYSQL) {
            $createQuery = $createQueries[0];
            $constraints = [];
            //ALTER TABLE oro_price_product ADD CONSTRAINT FK_F47F707A1F68CB0D FOREIGN KEY (price_rule_id) REFERENCES oro_price_rule (id) ON DELETE CASCADE
            foreach ($createQueries as $query) {
                if (strpos($query, 'ALTER TABLE') === 0) {
                    $constraints[] = substr($query, strpos($query, 'CONSTRAINT'));
                }
            }
            $additionalSql = implode(', ', $constraints);
            $createQuery = str_replace('PRIMARY KEY(id)', 'PRIMARY KEY(id), ' . $additionalSql, $createQuery);
            $createQueries = [$createQuery];
        }

        return $createQueries;
    }
}
