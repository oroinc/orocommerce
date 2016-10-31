<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_0;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class RenameInventoryConfigSectionQuery extends ParametrizedMigrationQuery
{
    /** @var string */
    private $oldSection;

    /** @var string */
    private $newSection;

    /** @var string */
    private $configName;

    /**
     * @param string $oldSection
     * @param string $newSection
     * @param string $configName
     */
    public function __construct($oldSection, $newSection, $configName)
    {
        $this->oldSection = $oldSection;
        $this->newSection = $newSection;
        $this->configName = $configName;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->processQueries($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->processQueries($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function processQueries(LoggerInterface $logger, $dryRun = false)
    {
        $query = 'UPDATE oro_config_value SET section = ? WHERE section = ? AND name = ?';
        $parameters = [$this->newSection, $this->oldSection, $this->configName];

        $this->logQuery($logger, $query, $parameters);
        if (!$dryRun) {
            $this->connection->executeUpdate($query, $parameters);
        }
    }
}
