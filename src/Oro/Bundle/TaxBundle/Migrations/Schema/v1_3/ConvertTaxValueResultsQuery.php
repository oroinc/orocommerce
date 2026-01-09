<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class ConvertTaxValueResultsQuery extends ParametrizedMigrationQuery
{
    public const SELECT_LIMIT = 1000;

    /**
     * @var AbstractPlatform
     */
    private $platform;

    public function __construct(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @throws \Doctrine\DBAL\Exception
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $objectType = Type::getType(Types::OBJECT);
        $jsonType = Type::getType(Types::JSON);

        $selectSql = 'SELECT id, result_base64 FROM oro_tax_value ORDER BY id LIMIT :lim OFFSET :offs';
        $selectTypes = ['lim' => Types::INTEGER, 'offs' => Types::INTEGER];
        $selectParams = ['lim' => self::SELECT_LIMIT, 'offs' => 0];

        $updateSql = 'UPDATE oro_tax_value SET result=:result WHERE id=:id';
        $updateTypes = ['id' => Types::INTEGER, 'result' => Types::STRING];

        if ($dryRun) {
            $this->logQuery($logger, $selectSql, $selectParams, $selectTypes);
        } else {
            $selectStatement = $this->connection->prepare($selectSql);
            $selectStatement->bindValue('lim', $selectParams['lim'], ParameterType::INTEGER);
            $updateStatement = $this->connection->prepare($updateSql);
            do {
                $selectStatement->bindValue('offs', $selectParams['offs'], ParameterType::INTEGER);
                $this->logQuery($logger, $selectSql, $selectParams, $selectTypes);
                $rowsCount = 0;
                while ($row = $selectStatement->executeQuery()->fetchAssociative()) {
                    $rowsCount++;
                    $result = $objectType->convertToPHPValue($row['result_base64'], $this->platform);
                    $updateParams = [
                        'id' => $row['id'],
                        'result' => $jsonType->convertToDatabaseValue($result, $this->platform)
                    ];
                    $updateStatement->bindValue('id', $updateParams['id'], ParameterType::INTEGER);
                    $updateStatement->bindValue('result', $updateParams['result'], ParameterType::STRING);
                    $updateStatement->executeQuery();
                    $this->logQuery($logger, $updateSql, $updateParams, $updateTypes);
                }
                $selectParams['offs'] += self::SELECT_LIMIT;
            } while ($rowsCount === self::SELECT_LIMIT);
        }
    }
}
