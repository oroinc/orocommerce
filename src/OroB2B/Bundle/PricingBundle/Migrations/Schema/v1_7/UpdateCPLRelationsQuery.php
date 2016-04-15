<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_7;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

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
        $query  = 'UPDATE ' . $this->tableName . ' SET full_combined_price_list_id = combined_price_list_id';
        $this->logQuery($logger, $query);
    }
}
