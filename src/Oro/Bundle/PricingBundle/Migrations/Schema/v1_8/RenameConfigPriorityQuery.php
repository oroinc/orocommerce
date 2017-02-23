<?php
namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class RenameConfigPriorityQuery extends ParametrizedMigrationQuery
{
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
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $this->renamePriorityInConfigValues($logger, $dryRun);
    }

    protected function renamePriorityInConfigValues(LoggerInterface $logger, $dryRun = false)
    {
        $selectQuery = 'SELECT id, array_value FROM oro_config_value WHERE name = :name AND section = :section LIMIT 1';

        $selectQueryParameters = [
            'name' => 'default_price_lists',
            'section' => 'oro_pricing',
        ];

        $selectQueryTypes = [
            'name' => 'string',
            'section' => 'string',
        ];

        $this->logQuery($logger, $selectQuery, $selectQueryParameters, $selectQueryTypes);
        $result = $this->connection->fetchAssoc($selectQuery, $selectQueryParameters, $selectQueryTypes);

        $arrayType = Type::getType(Type::TARRAY);
        $platform = $this->connection->getDatabasePlatform();

        $defaultPriceLists = $arrayType->convertToPHPValue($result['array_value'], $platform);

        if (count($defaultPriceLists) > 0) {
            foreach ($defaultPriceLists as $key => $priceList) {
                if (array_key_exists(RenamePriority::OLD_COLUMN_NAME, $priceList)) {
                    $priceList[RenamePriority::NEW_COLUMN_NAME] = $priceList[RenamePriority::OLD_COLUMN_NAME];
                    unset($priceList[RenamePriority::OLD_COLUMN_NAME]);

                    $defaultPriceLists[$key] = $priceList;
                }
            }

            $updateQuery = 'UPDATE oro_config_value SET array_value = :array_value WHERE id = :id';
            $updateQueryParameters = [
                'array_value' => $defaultPriceLists,
                'id' => $result['id'],
            ];
            $updateQueryTypes = [
                'array_value' => 'array',
                'id' => 'integer',
            ];

            $this->logQuery($logger, $updateQuery, $updateQueryParameters, $updateQueryTypes);
            if (!$dryRun) {
                $this->connection->executeUpdate($updateQuery, $updateQueryParameters, $updateQueryTypes);
            }
        }
    }
}
