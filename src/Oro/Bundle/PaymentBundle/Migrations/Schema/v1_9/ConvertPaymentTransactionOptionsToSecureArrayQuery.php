<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\PaymentBundle\DBAL\Types\SecureArrayType;
use Psr\Log\LoggerInterface;

class ConvertPaymentTransactionOptionsToSecureArrayQuery extends ParametrizedMigrationQuery
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
        $arrayType = Type::getType(Types::ARRAY);
        $secureArrayType = Type::getType(SecureArrayType::TYPE);

        $selectSql = 'SELECT id, transaction_options_old 
            FROM oro_payment_transaction ORDER BY id LIMIT :limit OFFSET :offset';

        $selectTypes = ['limit' => Types::INTEGER, 'offset' => Types::INTEGER];
        $selectParams = ['limit' => self::SELECT_LIMIT, 'offset' => 0];

        $selectStatement = $this->connection->prepare($selectSql);
        $selectStatement->bindValue('limit', $selectParams['limit'], \PDO::PARAM_INT);

        $updateSql = 'UPDATE oro_payment_transaction SET transaction_options=:transactionOptions WHERE id=:id';
        $updateTypes = ['id' => Types::INTEGER, 'transactionOptions' => Types::STRING];
        $updateStatement = $this->connection->prepare($updateSql);

        do {
            $selectStatement->bindValue('offset', $selectParams['offset'], \PDO::PARAM_INT);
            $selectStatement->execute();
            $this->logQuery($logger, $selectSql, $selectParams, $selectTypes);
            $rowsCount = 0;
            while ($row = $selectStatement->fetch()) {
                $rowsCount++;

                $currentTransactionOptions = $arrayType->convertToPHPValue(
                    $row['transaction_options_old'],
                    $this->platform
                );

                $newTransactionOptions = $secureArrayType->convertToDatabaseValue(
                    $currentTransactionOptions,
                    $this->platform
                );

                $updateParams = [
                    'id' => $row['id'],
                    'transactionOptions' => $newTransactionOptions,
                ];
                $updateStatement->bindValue('id', $updateParams['id'], \PDO::PARAM_INT);
                $updateStatement->bindValue('transactionOptions', $updateParams['transactionOptions'], \PDO::PARAM_STR);

                if ($dryRun) {
                    $this->logQuery($logger, $updateSql, $updateParams, $updateTypes);
                } else {
                    $updateStatement->execute();
                }
            }

            $selectParams['offset'] += self::SELECT_LIMIT;
        } while ($rowsCount === self::SELECT_LIMIT);
    }
}
