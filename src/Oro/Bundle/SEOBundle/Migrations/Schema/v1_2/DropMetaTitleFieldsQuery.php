<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Connection;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;
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
     * @param DbIdentifierNameGenerator $nameGenerator
     */
    public function __construct(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
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
        $this->removeMetaTitles(Product::class, $logger, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function removePageMetaTitles(LoggerInterface $logger, $dryRun = false)
    {
        $this->removeMetaTitles(Page::class, $logger, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function removeCategoryMetaTitles(LoggerInterface $logger, $dryRun = false)
    {
        $this->removeMetaTitles(Category::class, $logger, $dryRun);
    }

    /**
     * @param string $sourceClassName
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function removeMetaTitles($sourceClassName, LoggerInterface $logger, $dryRun = false)
    {
        $query = sprintf(
            'DELETE FROM oro_fallback_localization_val WHERE id IN (SELECT localizedfallbackvalue_id FROM %s);',
            $this->nameGenerator->generateManyToManyJoinTableName(
                $sourceClassName,
                'metaTitles',
                LocalizedFallbackValue::class
            )
        );

        $logger->info($query);

        if (!$dryRun) {
            $this->connection->executeQuery($query);
        }
    }
}
