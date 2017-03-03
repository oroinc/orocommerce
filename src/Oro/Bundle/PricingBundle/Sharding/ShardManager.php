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

// todo: investigate how transactions work in MySQL(implicit commit) new connection may be required
class ShardManager
{
    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $className
     * @param array $attributes
     * @return string
     *
     * @throws \Exception
     */
    public function getShardName($className, array $attributes)
    {
        $baseTableName = $this->getBaseTableName($className);
        if (!isset($attributes['priceList']) || !is_a($attributes['priceList'], PriceList::class)) {
            throw new \Exception(sprintf("Required attribute '%s' for generation of shard name missing.", "priceList"));
        }

        // todo: investigate best way to pass attributes and validate
        /** @var PriceList $priceList */
        $priceList = $attributes['priceList'];
        $shardName = sprintf("%s_%s", $baseTableName, $priceList->getId());

        return $shardName;
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

        // in PostgreSQL index and fk names should be unique in schema
        $search = [$baseTableName];
        $replace = [$shardName];
        foreach ($table->getIndexes() as $index) {
            $search[] = $index->getName();
            $replace[] = $this->generateIdentifierName($shardName, $index);
        }
        foreach ($table->getIndexes() as $foreignKey) {
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

        // tableExists fetches full list of tables on every call
        // @todo: investigate if it should be refactored
        return $connection->getSchemaManager()->tablesExist($shardName);
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
}
