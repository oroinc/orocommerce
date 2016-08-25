<?php

namespace Oro\Bundle\FrontendBundle\Migration;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateSerializedClassNames extends ParametrizedMigrationQuery
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

        $statement = $this->connection->query("SELECT id, $field FROM $table");

        while ($entity = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $originalValue = base64_decode($entity[$field]);

            preg_match_all('/\"(OroB2B.*?)\"/', $originalValue, $matches, PREG_SET_ORDER);
            if (!empty($matches)) {
                // set aliases to allow deserialization of not existing classes
                foreach ($matches as $match) {
                    $oldClassName = $match[1];
                    if (!class_exists($oldClassName)) {
                        $realClassName = preg_replace('/^OroB2B/', 'Oro', $oldClassName, 1);
                        class_alias($realClassName, $oldClassName);
                    }
                }

                $object = unserialize($originalValue);
                $alteredValue = base64_encode(serialize($object));

                $query = "UPDATE $table SET $field = ? WHERE id = ?";
                $parameters = [$alteredValue, $entity['id']];

                $this->logQuery($logger, $query, $parameters);
                if (!$dryRun) {
                    $this->connection->executeUpdate($query, $parameters);
                }
            }
        }
    }
}
