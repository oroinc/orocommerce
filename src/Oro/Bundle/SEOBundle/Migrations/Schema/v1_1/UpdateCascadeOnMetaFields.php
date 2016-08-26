<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Connection;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\ProductBundle\Entity\Product;
use Psr\Log\LoggerInterface;

class UpdateCascadeOnMetaFields extends ParametrizedMigrationQuery
{

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Update meta fields relations with cascade (all) options';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $sql = 'SELECT field_conf.id, field_conf.data FROM oro_entity_config_field field_conf
                JOIN oro_entity_config as conf
                ON field_conf.entity_id = conf.id
                WHERE conf.class_name IN (:classes) AND field_conf.field_name IN (:fieldNames)';
        $params = [
            'classes' => [Product::class, Category::class, Page::class],
            'fieldNames' => ['metaTitles', 'metaDescriptions', 'metaKeywords'],
        ];
        $types = [
            'classes' => Connection::PARAM_STR_ARRAY,
            'fieldNames' => Connection::PARAM_STR_ARRAY,
        ];

        $this->logQuery($logger, $sql, $params, $types);

        $rows = $this->connection->fetchAll($sql, $params, $types);

        $this->addCascadeOptionAndUpdate($logger, $rows);
    }

    /**
     * @param LoggerInterface $logger
     * @param array $rows
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function addCascadeOptionAndUpdate(LoggerInterface $logger, array $rows = [])
    {
        $sql = 'UPDATE oro_entity_config_field SET data = :data WHERE id = :id';
        $types = ['data' => 'array', 'id' => 'integer'];

        foreach ($rows as $key => $row) {
            $rows[$key]['data'] = $this->connection->convertToPHPValue($row['data'], 'array');
            $rows[$key]['data']['extend']['cascade'] = ['all'];

            $params = ['data' => $rows[$key]['data'], 'id' => $rows[$key]['id']];

            $this->logQuery($logger, $sql, $params, $types);
            $this->connection->executeUpdate($sql, $params, $types);
        }
    }
}
