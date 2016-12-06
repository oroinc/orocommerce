<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\ProductBundle\Entity\Product;
use Psr\Log\LoggerInterface;

class DropMetaTitlesEntityConfigValuesQuery extends ParametrizedMigrationQuery
{
    const FIELD_NAME = 'metaTitles';

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        foreach ([Product::class, Page::class, Category::class] as $className) {
            $this->deleteFieldIndex($className, $logger, $dryRun);
            $this->deleteField($className, $logger, $dryRun);
            $this->updateEntityData($className, $logger, $dryRun);
        }
    }

    /**
     * @param string $className
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function deleteFieldIndex($className, LoggerInterface $logger, $dryRun = false)
    {
        $query = <<<'SQL'
DELETE FROM oro_entity_config_index_value
WHERE field_id  = (
    SELECT id FROM oro_entity_config_field
    WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = :class)
    AND field_name = :field_name
)
SQL;
        $params = [
            'class' => $className,
            'field_name' => self::FIELD_NAME
        ];

        $this->logQuery($logger, $query, $params);

        if (!$dryRun) {
            $this->connection->executeQuery($query, $params);
        };
    }

    /**
     * @param string $className
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function deleteField($className, LoggerInterface $logger, $dryRun = false)
    {
        $query = <<<'SQL'
DELETE FROM oro_entity_config_field
WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = :class)
AND field_name = :field_name
SQL;
        $params = [
            'class' => $className,
            'field_name' => self::FIELD_NAME
        ];

        $this->logQuery($logger, $query, $params);

        if (!$dryRun) {
            $this->connection->executeQuery($query, $params);
        };
    }

    /**
     * @param string $className
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function updateEntityData($className, LoggerInterface $logger, $dryRun = false)
    {
        $sql = 'SELECT e.data FROM oro_entity_config as e WHERE e.class_name = ? LIMIT 1';
        $entityRow = $this->connection->fetchAssoc($sql, [$className]);
        $data = $entityRow['data'];

        $data = $data ? $this->connection->convertToPHPValue($data, Type::TARRAY) : [];

        $key = sprintf('manyToMany|%s|%s|%s', $className, LocalizedFallbackValue::class, self::FIELD_NAME);

        if (isset($data['extend']['relation'][$key])) {
            unset($data['extend']['relation'][$key]);
        }

        if (isset($data['extend']['schema']['relation'][self::FIELD_NAME])) {
            unset($data['extend']['schema']['relation'][self::FIELD_NAME]);
        }

        if (isset($data['extend']['schema']['addremove'][self::FIELD_NAME])) {
            unset($data['extend']['schema']['addremove'][self::FIELD_NAME]);
        }

        $data = $this->connection->convertToDatabaseValue($data, Type::TARRAY);

        $query = 'UPDATE oro_entity_config SET data = :data WHERE class_name = :class';
        $params = [
            'data' => $data,
            'class' => $className
        ];

        $this->logQuery($logger, $query, $params);

        if (!$dryRun) {
            $this->connection->executeQuery($query, $params);
        };
    }
}
