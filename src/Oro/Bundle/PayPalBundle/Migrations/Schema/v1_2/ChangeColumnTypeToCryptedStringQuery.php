<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Psr\Log\LoggerInterface;

class ChangeColumnTypeToCryptedStringQuery extends ParametrizedSqlMigrationQuery
{
    /** @var string */
    private $table;

    /** @var string[] */
    private $fields;

    public function __construct(string $table, array $fields)
    {
        $this->table = $table;
        $this->fields = $fields;

        parent::__construct();
    }

    public function processQueries(LoggerInterface $logger, $dryRun = false)
    {
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof MySqlPlatform) {
            foreach ($this->fields as $field) {
                $this->addSql(
                    "ALTER TABLE {$this->table} " .
                    "CHANGE {$field} {$field} VARCHAR(255) NULL DEFAULT NULL COMMENT '(DC2Type:crypted_string)'"
                );
            }
        } elseif ($platform instanceof PostgreSqlPlatform) {
            foreach ($this->fields as $field) {
                $this->addSql("COMMENT ON COLUMN {$this->table}.{$field} IS '(DC2Type:crypted_string)'");
            }
        }

        parent::processQueries($logger, $dryRun);
    }
}
