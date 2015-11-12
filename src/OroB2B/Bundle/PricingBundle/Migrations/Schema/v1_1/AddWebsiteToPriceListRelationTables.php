<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AddWebsiteToPriceListRelationTables implements Migration, ContainerAwareInterface, DatabasePlatformAwareInterface
{

    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
         $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addWebsiteToOroB2BPriceListToAccount($schema, $queries);
        $this->addWebsiteToOroB2BPriceListToAccountGroup($schema, $queries);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function addWebsiteToOroB2BPriceListToAccount(Schema $schema, QueryBag $queries)
    {
        $this->addWebsiteToRelationTable($schema, $queries, 'orob2b_price_list_to_account', 'account_id');
    }

    /**
     * @param Schema $schema
     */
    protected function addWebsiteToOroB2BPriceListToAccountGroup(Schema $schema, QueryBag $queries)
    {
        $this->addWebsiteToRelationTable($schema, $queries, 'orob2b_price_list_to_c_group', 'account_group_id');
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @param $tableName
     * @param $relationField
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addWebsiteToRelationTable(Schema $schema, QueryBag $queries, $tableName, $relationField)
    {
        $table = $schema->getTable($tableName);
        $table->dropPrimaryKey();
        $table->addColumn('website_id', 'integer', ['default' => $this->getDefaultWebsite()->getId()]);
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->setPrimaryKey(['price_list_id', $relationField, 'website_id']);
        $queries->addPostQuery($this->createWebsiteDefaultNullQuery($schema, $tableName));
    }

    /**
     * @param Schema $schema
     * @param $tableName
     * @return SqlMigrationQuery
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function createWebsiteDefaultNullQuery(Schema $schema, $tableName)
    {
        $currentSchema  = new Schema([clone $schema->getTable($tableName)]);
        $requiredSchema = new Schema([clone $schema->getTable($tableName)]);
        $table = $requiredSchema->getTable($tableName);
        $table->changeColumn('website_id', ['default' => null]);

        $comparator = new Comparator();
        $changes = $comparator->compare($currentSchema, $requiredSchema)->toSql($this->platform);
        return new SqlMigrationQuery($changes);
    }

    /**
     * @return \OroB2B\Bundle\WebsiteBundle\Entity\Website
     */
    protected function getDefaultWebsite()
    {
        return $this->container->get('doctrine')->getManagerForClass('OroB2BWebsiteBundle:Website')
            ->getRepository('OroB2BWebsiteBundle:Website')->getDefaultWebsite();
    }
}
