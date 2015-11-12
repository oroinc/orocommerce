<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_1;

use Doctrine\Common\Collections\Criteria;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class InsertSelectPriceListRelationTablesQuery extends ParametrizedMigrationQuery
{
    /**
     * @var int
     */
    protected static $defaultWebsiteId;

    /**
     * @var string
     */
    protected $oldTableName;

    /**
     * @var string
     */
    protected $newTableName;

    /**
     * @var string
     */
    protected $field;

    /**
     * @param string $newTableName
     * @param string $oldTableName
     * @param string|null $field
     */
    public function __construct($newTableName, $oldTableName, $field)
    {
        $this->newTableName = $newTableName;
        $this->oldTableName = $oldTableName;
        $this->field = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Copy values from old to new price list relation table with priority and website';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $fields = ['price_list_id'];
        $websiteId = 'website_id';
        if ($this->field !== 'website_id') {
            $fields[] = $this->field;
            $websiteId = $this->getDefaultWebsiteId();
        }

        $insertFields = implode(', ', array_merge($fields, ['website_id', 'priority']));
        $selectFields = implode(', ', array_merge($fields, [$websiteId, 100]));

        $sql = <<<SQL
  INSERT INTO
    {$this->newTableName}
  ($insertFields)
    SELECT
      $selectFields
    FROM {$this->oldTableName};
SQL;
        $this->logQuery($logger, $sql);
        $this->connection->exec($sql);
    }

    /**
     * @return \OroB2B\Bundle\WebsiteBundle\Entity\Website
     */
    protected function getDefaultWebsiteId()
    {
        if (!static::$defaultWebsiteId) {
            static::$defaultWebsiteId = $this->connection->createQueryBuilder()
                ->select('id')
                ->from('orob2b_website')
                ->orderBy('id', Criteria::ASC)
                ->setMaxResults(1)
                ->execute()->fetchColumn();
        }

        return static::$defaultWebsiteId;
    }
}
