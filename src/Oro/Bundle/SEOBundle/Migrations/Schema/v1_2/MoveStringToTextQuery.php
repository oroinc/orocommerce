<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_2;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

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

                $this->logQuery($logger, $query, $parameters);
                if (!$dryRun) {
                    $this->connection->executeUpdate($query, $parameters);
                }
            }
        }
    }
}
