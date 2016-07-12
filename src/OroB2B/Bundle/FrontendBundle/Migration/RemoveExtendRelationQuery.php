<?php

namespace OroB2B\Bundle\FrontendBundle\Migration;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class RemoveExtendRelationQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    private $entityFrom;

    /**
     * @var string
     */
    private $entityTo;

    /**
     * @var string
     */
    private $relationName;

    /**
     * @var string
     */
    private $relationType;

    /**
     * @param string $entityFrom
     * @param string $entityTo
     * @param string $relationName
     * @param string $relationType
     */
    public function __construct($entityFrom, $entityTo, $relationName, $relationType)
    {
        $this->entityFrom = $entityFrom;
        $this->entityTo = $entityTo;
        $this->relationName = $relationName;
        $this->relationType = $relationType;
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
        $row = $this->connection->fetchAssoc(
            'SELECT id, data from oro_entity_config WHERE class_name = ? LIMIT 1'
            [$this->entityFrom]
        );
        if ($row) {
            $id = $row['id'];
            $originalData = $row['data'];
            $originalData = $originalData ? $this->connection->convertToPHPValue($originalData, Type::TARRAY) : [];

            $data = $originalData;
            $fullRelation = implode(
                '|',
                [$this->relationType, $this->entityFrom, $this->entityTo, $this->relationName]
            );
            if (isset($data['extend']['relation'][$fullRelation])) {
                unset($data['extend']['relation'][$fullRelation]);
            }
            if (isset($data['extend']['schema']['relation'][$this->relationName])) {
                unset($data['extend']['schema']['relation'][$this->relationName]);
            }
            if (isset($data['extend']['schema']['addremove'][$this->relationName])) {
                unset($data['extend']['schema']['addremove'][$this->relationName]);
            }

            if ($data !== $originalData) {
                $query = 'UPDATE oro_entity_config SET data = ? WHERE id = ?';
                $parameters = [$this->connection->convertToDatabaseValue($data, Type::TARRAY), $id];

                $this->logQuery($logger, $query, $parameters);
                if (!$dryRun) {
                    $this->connection->executeUpdate($query, $parameters);
                }
            }
        }
    }
}
