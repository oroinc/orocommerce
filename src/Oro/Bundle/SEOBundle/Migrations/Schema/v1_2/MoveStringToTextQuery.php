<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class MoveStringToTextQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    private $table;

    /**
     * @param string $table
     */
    public function __construct($table)
    {
        $this->table = $table;
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
     * @param bool            $dryRun
     */
    protected function processQueries(LoggerInterface $logger, $dryRun = false)
    {
        $table = $this->table;

        $statement = $this->connection->query(
            "select f.id, f.string, f.text " .
            "from oro_fallback_localization_val f inner join $table j on f.id = j.localizedfallbackvalue_id"
        );

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            if (!empty($row['string'])) {
                $query = "UPDATE oro_fallback_localization_val SET string = NULL, text = ? WHERE id = ?";
                $parameters = [$row['string'], $row['id']];
                $types = [Types::STRING, Types::INTEGER];

                $this->logQuery($logger, $query, $parameters, $types);
                if (!$dryRun) {
                    $this->connection->executeStatement($query, $parameters, $types);
                }
            }
        }
    }
}
