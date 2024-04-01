<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Sets shopping list currency attribute value based on first shopping list total or default currency.
 */
class AddOroShoppingListCurrencyColumnStage2Query extends ParametrizedMigrationQuery
{
    private string $defaultCurrency;

    public function __construct(string $defaultCurrency)
    {
        $this->defaultCurrency = $defaultCurrency;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): array
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger): void
    {
        $this->doExecute($logger);
    }

    private function doExecute(LoggerInterface $logger, bool $dryRun = false): void
    {
        $identifiers = $this->getIdentifiers($logger);

        if (!$identifiers) {
            return;
        }

        $types = [Types::STRING, Connection::PARAM_INT_ARRAY];
        $query = 'UPDATE oro_shopping_list SET currency = ? WHERE id in (?)';

        foreach (array_chunk($identifiers, 500) as $chunk) {
            if ($currencies = $this->getCurrencies($logger, $chunk)) {
                foreach ($currencies as $currency => $ids) {
                    $params = [$currency, $ids];
                    $this->logQuery($logger, $query, $params, $types);
                    if (!$dryRun) {
                        $this->connection->executeStatement($query, $params, $types);
                    }
                }
            }
        }

        $query = 'UPDATE oro_shopping_list SET currency = ? WHERE currency IS NULL';
        $types = [Types::STRING];
        $params = [$this->defaultCurrency];

        if (!$dryRun) {
            $this->connection->executeStatement($query, $params, $types);
        }
    }

    private function getIdentifiers(LoggerInterface $logger): array
    {
        $query = 'SELECT id FROM oro_shopping_list';

        $this->logQuery($logger, $query);

        return array_column($this->connection->fetchAllAssociative($query), 'id');
    }

    private function getCurrencies(LoggerInterface $logger, array $identifiers): array
    {
        $currencies = [];
        $query = 'SELECT DISTINCT ON(shopping_list_id) shopping_list_id, currency 
                FROM oro_shopping_list_total 
                WHERE shopping_list_id IN (?)';

        $params = [$identifiers];
        $types = [Connection::PARAM_INT_ARRAY];
        $this->logQuery($logger, $query, $params, $types);

        if ($rows = $this->connection->fetchAllAssociative($query, $params, $types)) {
            foreach ($rows as $row) {
                $currencies[$row['currency']][] = $row['shopping_list_id'];
            }
        }

        return $currencies;
    }
}
