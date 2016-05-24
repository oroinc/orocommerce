<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_3;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateCPLRelationsQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * @param string $className
     */
    public function __construct($className)
    {
        $this->tableName = $className;
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
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $qb = $this->connection
            ->createQueryBuilder()
            ->update($this->tableName)
            ->set('full_combined_price_list_id', 'combined_price_list_id')
        ;

        $this->logQuery($logger, $qb->getSql());
        if (!$dryRun) {
            $qb->execute();
        }
    }
}
