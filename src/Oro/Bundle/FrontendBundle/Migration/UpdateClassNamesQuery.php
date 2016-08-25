<?php

namespace Oro\Bundle\FrontendBundle\Migration;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class UpdateClassNamesQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $field;

    /**
     * @param string $table
     * @param string $field
     */
    public function __construct($table, $field)
    {
        $this->table = $table;
        $this->field = $field;
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
        $field = $this->field;

        $statement = $this->connection->query("SELECT id, $field FROM $table WHERE $field LIKE 'Oro%'");

        while ($entity = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $originalClass = $entity[$field];
            $class = preg_replace('/^Oro/', 'Oro', $originalClass, 1);

            $query = "UPDATE $table SET $field = ? WHERE id = ?";
            $parameters = [$class, $entity['id']];

            $this->logQuery($logger, $query, $parameters);
            if (!$dryRun) {
                $this->connection->executeUpdate($query, $parameters);
            }
        }
    }
}
