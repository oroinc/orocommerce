<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class ConvertTaxValueResultsQuery extends ParametrizedMigrationQuery
{
    const SELECT_LIMIT = 1000;

    /**
     * @var AbstractPlatform
     */
    private $platform;

    public function __construct(AbstractPlatform $platform)
    {
        $this->platform = $platform;
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
        $objectType = Type::getType(Types::OBJECT);
        $jsonType = Type::getType(Types::JSON_ARRAY);

        $selectSql = 'SELECT id, result_base64 FROM oro_tax_value ORDER BY id LIMIT :lim OFFSET :offs';
        $selectTypes = ['lim' => Types::INTEGER, 'offs' => Types::INTEGER];
        $selectParams = ['lim' => self::SELECT_LIMIT, 'offs' => 0];

        $updateSql = 'UPDATE oro_tax_value SET result=:result WHERE id=:id';
        $updateTypes = ['id' => Types::INTEGER, 'result' => Types::STRING];

        if ($dryRun) {
            $this->logQuery($logger, $selectSql, $selectParams, $selectTypes);
        } else {
            $selectStatement = $this->connection->prepare($selectSql);
            $selectStatement->bindValue('lim', $selectParams['lim'], \PDO::PARAM_INT);
            $updateStatement = $this->connection->prepare($updateSql);
            do {
                $selectStatement->bindValue('offs', $selectParams['offs'], \PDO::PARAM_INT);
                $selectStatement->execute();
                $this->logQuery($logger, $selectSql, $selectParams, $selectTypes);
                $rowsCount = 0;
                while ($row = $selectStatement->fetch()) {
                    $rowsCount++;
                    $result = $objectType->convertToPHPValue($row['result_base64'], $this->platform);
                    $updateParams = [
                        'id' => $row['id'],
                        'result' => $jsonType->convertToDatabaseValue($result, $this->platform)
                    ];
                    $updateStatement->bindValue('id', $updateParams['id'], \PDO::PARAM_INT);
                    $updateStatement->bindValue('result', $updateParams['result'], \PDO::PARAM_STR);
                    $updateStatement->execute();
                    $this->logQuery($logger, $updateSql, $updateParams, $updateTypes);
                }
                $selectParams['offs'] += self::SELECT_LIMIT;
            } while ($rowsCount === self::SELECT_LIMIT);
        }
    }
}
