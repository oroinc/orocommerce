<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Connection;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;
use Psr\Log\LoggerInterface;

class DropMetaTitleFieldsQuery implements
    MigrationQuery,
    ConnectionAwareInterface,
    NameGeneratorAwareInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * @param DbIdentifierNameGenerator $nameGenerator
     * @param ExtendExtension $extendExtension
     */
    public function __construct(DbIdentifierNameGenerator $nameGenerator, ExtendExtension $extendExtension)
    {
        $this->nameGenerator = $nameGenerator;
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

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
        $this->removeProductMetaTitles($logger, $dryRun);
        $this->removePageMetaTitles($logger, $dryRun);
        $this->removeCategoryMetaTitles($logger, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function removeProductMetaTitles(LoggerInterface $logger, $dryRun = false)
    {
        $this->removeMetaTitles('oro_product', $logger, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function removePageMetaTitles(LoggerInterface $logger, $dryRun = false)
    {
        $this->removeMetaTitles('oro_cms_page', $logger, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function removeCategoryMetaTitles(LoggerInterface $logger, $dryRun = false)
    {
        $this->removeMetaTitles('oro_catalog_category', $logger, $dryRun);
    }

    /**
     * @param string $table
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function removeMetaTitles($table, LoggerInterface $logger, $dryRun = false)
    {
        $query = sprintf(
            'DELETE FROM oro_fallback_localization_val WHERE id IN (SELECT localizedfallbackvalue_id FROM %s);',
            $this->getAssociationTableName($table, 'oro_fallback_localization_val')
        );

        $logger->info($query);

        if (!$dryRun) {
            $this->connection->executeQuery($query);
        }
    }

    /**
     * @param string $sourceTable
     * @param string $targetTable
     * @return string
     */
    protected function getAssociationTableName($sourceTable, $targetTable)
    {
        $sourceClassName = $this->extendExtension->getEntityClassByTableName($sourceTable);
        $targetClassName = $this->extendExtension->getEntityClassByTableName($targetTable);

        return $this->nameGenerator->generateManyToManyJoinTableName(
            $sourceClassName,
            'metaTitles',
            $targetClassName
        );
    }
}
