<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_5;

use Psr\Log\LoggerInterface;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateMaterializedPathQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info('Update materialized path for categories');
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
     * @throws \Doctrine\DBAL\ConnectionException
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $sql = 'SELECT * FROM oro_catalog_category ORDER BY tree_left';
        $this->logQuery($logger, $sql);
        $result = $this->connection->fetchAll($sql);

        $this->connection->beginTransaction();
        $categories = [];
        foreach ($result as $item) {
            $categories[$item['id']] = $item;
        }

        foreach ($categories as &$item) {
            $item['materialized_path'] = $item['id'];
            if (!empty($item['parent_id']) && !empty($categories[$item['parent_id']]['materialized_path'])) {
                $item['materialized_path'] = sprintf(
                    '%s%s%s',
                    $categories[$item['parent_id']]['materialized_path'],
                    Category::MATERIALIZED_PATH_DELIMITER,
                    $item['materialized_path']
                );
            }
            $this->connection->update(
                'oro_catalog_category',
                ['materialized_path' => $item['materialized_path']],
                ['id' => $item['id']]
            );
        }

        if (!$dryRun) {
            $this->connection->commit();
        }
    }
}
