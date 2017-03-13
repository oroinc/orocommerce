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
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ShardManager implements \Serializable
{
    /**
     * @var RegistryInterface
     */
    private $registry;

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
     * @todo investigate collisions with parallel processes
     */
    public function create($className, $shardName)
    {
        $baseTableName = $this->getBaseTableName($className);
        $connection = $this->getConnection($className);
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

        $createFlags = AbstractPlatform::CREATE_INDEXES|AbstractPlatform::CREATE_FOREIGNKEYS;
        $createQueries = $connection->getDatabasePlatform()->getCreateTableSQL($table, $createFlags);
        foreach ($createQueries as $key => $query) {
            $query = str_replace($search, $replace, $query);
            $connection->executeQuery($query);
        }
    }

    /**
     * @param string $className
     * @param string $shardName
     * @return bool
     */
    public function exists($className, $shardName)
    {
        $connection = $this->getConnection($className);
        return $connection->getSchemaManager()->tablesExist([$shardName]);
    }

    /**
     * @param string $className
     * @param string $shardName
     */
    public function delete($className, $shardName)
    {
        $this->getConnection($className)->getSchemaManager()->dropTable($shardName);
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
     * @param $className
     * @return Connection
     */
    protected function getConnection($className)
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass($className);
        $connection = $em->getConnection();

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
        //TODO: get from annotations
        return 'priceList';
    }

    /**
     * @param $className
     * @return string
     */
    public function getDiscriminationColumn($className)
    {
        //TODO: get from annotations
        return 'price_list_id';
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
}
