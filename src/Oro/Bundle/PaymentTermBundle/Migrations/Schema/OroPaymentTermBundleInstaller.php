<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareInterface;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareTrait;

class OroPaymentTermBundleInstaller implements
    Installation,
    PaymentTermExtensionAwareInterface,
    DatabasePlatformAwareInterface,
    RenameExtensionAwareInterface,
    ContainerAwareInterface,
    ActivityExtensionAwareInterface
{
    use PaymentTermExtensionAwareTrait, ContainerAwareTrait, UpdateExtendRelationTrait;

    /** @var AbstractPlatform */
    protected $platform;

    /** @var RenameExtension */
    protected $renameExtension;

    /**
     * @var ActivityExtension
     */
    protected $activityExtension;

    /**
     * Table name for PaymentTerm
     */
    const TABLE_NAME = 'oro_payment_term';
    const PAYMENT_TERM_TO_ACCOUNT_TABLE = 'oro_payment_term_to_account';
    const PAYMENT_TERM_TO_ACCOUNT_GROUP_TABLE = 'oro_payment_term_to_acc_grp';
    const ACCOUNT_TABLE = 'oro_account';
    const ACCOUNT_GROUP_TABLE = 'oro_account_group';

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema->hasTable(self::TABLE_NAME)) {
            $this->migrate($schema, $queries);

            return;
        }

        $this->createOroPaymentTermTable($schema);

        $this->activityExtension->addActivityAssociation($schema, 'oro_note', self::TABLE_NAME);

        $this->paymentTermExtension->addPaymentTermAssociation($schema, 'oro_account');
        $this->paymentTermExtension->addPaymentTermAssociation($schema, 'oro_account_group');
    }

    /**
     * Create table for PaymentTerm entity
     *
     * @param Schema $schema
     */
    protected function createOroPaymentTermTable(Schema $schema)
    {
        $table = $schema->createTable(self::TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('label', 'string');
        $table->setPrimaryKey(['id']);
    }

    /**
     * {@inheritdoc}
     */
    public function migrate(Schema $schema, QueryBag $queries)
    {
        $this->paymentTermExtension->addPaymentTermAssociation($schema, 'oro_account');
        $this->paymentTermExtension->addPaymentTermAssociation($schema, 'oro_account_group');

        $notes = $schema->getTable('oro_note');
        if ($notes->hasForeignKey('fk_ba066ce1b2856c4b')) {
            $notes->removeForeignKey('fk_ba066ce1b2856c4b');
        }
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $notes,
            'payment_term_3dd15035_id',
            'payment_term_7c4f1e8e_id'
        );
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'oro_payment_term',
            ['payment_term_7c4f1e8e_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );

        $this->migrateConfig(
            $this->container->get('oro_entity_config.config_manager'),
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm',
            'payment_term_3dd15035',
            'payment_term_7c4f1e8e',
            RelationType::MANY_TO_ONE
        );
    }

    /**
     * @param QueryBag $queries
     */
    protected function migrateRelations(QueryBag $queries)
    {
        if ($this->platform instanceof MySqlPlatform) {
            $queryAccount = <<<QUERY
UPDATE oro_account a
JOIN oro_payment_term_to_account pta ON pta.account_id = a.id
SET a.payment_term_7c4f1e8e_id = pta.payment_term_id;
QUERY;
            $queryGroup = <<<QUERY
UPDATE oro_account_group ag
JOIN oro_payment_term_to_acc_grp ptag ON ptag.account_group_id = ag.id
SET ag.payment_term_7c4f1e8e_id = ptag.payment_term_id;
QUERY;
        } elseif ($this->platform instanceof PostgreSqlPlatform) {
            $queryAccount = <<<QUERY
UPDATE oro_account a
SET payment_term_7c4f1e8e_id = pta.payment_term_id
FROM oro_payment_term_to_account pta
WHERE pta.account_id = a.id;
QUERY;
            $queryGroup = <<<QUERY
UPDATE oro_account_group ag
SET payment_term_7c4f1e8e_id = ptag.payment_term_id
FROM oro_payment_term_to_acc_grp ptag
WHERE ptag.account_group_id = ag.id;
QUERY;
        }

        $configIndexValueSql = <<<QUERY
DELETE FROM oro_entity_config_index_value
WHERE field_id  = (
    SELECT id FROM oro_entity_config_field
    WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = :class)
    AND field_name = :field_name
)
QUERY;

        $configFieldSql = <<<QUERY
DELETE FROM oro_entity_config_field
WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = :class)
AND field_name = :field_name
QUERY;

        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                $configIndexValueSql,
                ['class' => 'Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm', 'field_name' => 'accounts']
            )
        );

        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                $configIndexValueSql,
                ['class' => 'Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm', 'field_name' => 'accountGroups']
            )
        );
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                $configFieldSql,
                ['class' => 'Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm', 'field_name' => 'accounts']
            )
        );
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                $configFieldSql,
                ['class' => 'Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm', 'field_name' => 'accountGroups']
            )
        );

        $queries->addPostQuery($queryAccount);
        $queries->addPostQuery($queryGroup);
        $queries->addPostQuery('DROP TABLE oro_payment_term_to_account;');
        $queries->addPostQuery('DROP TABLE oro_payment_term_to_acc_grp;');
    }

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }
}
