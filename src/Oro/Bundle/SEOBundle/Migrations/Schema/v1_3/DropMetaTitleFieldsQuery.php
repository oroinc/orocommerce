<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Connection;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
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
     * @var string
     */
    protected $className;

    /**
     * @param string $className
     * @param DbIdentifierNameGenerator $nameGenerator
     */
    public function __construct($className, DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
        $this->className = $className;
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
        $query = sprintf(
            'DELETE FROM oro_fallback_localization_val WHERE id IN (SELECT localizedfallbackvalue_id FROM %s);',
            $this->nameGenerator->generateManyToManyJoinTableName(
                $this->className,
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
