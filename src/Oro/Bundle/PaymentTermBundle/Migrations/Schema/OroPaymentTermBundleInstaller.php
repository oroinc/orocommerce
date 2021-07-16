<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;
use Oro\Bundle\NoteBundle\Migration\UpdateNoteAssociationKindQuery;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareInterface;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareTrait;

class OroPaymentTermBundleInstaller implements
    Installation,
    PaymentTermExtensionAwareInterface,
    DatabasePlatformAwareInterface,
    RenameExtensionAwareInterface,
    ActivityExtensionAwareInterface,
    NameGeneratorAwareInterface,
    ExtendExtensionAwareInterface
{
    use PaymentTermExtensionAwareTrait, UpdateExtendRelationTrait;

    /** @var AbstractPlatform */
    protected $platform;

    /** @var RenameExtension */
    protected $renameExtension;

    /**
     * @var ActivityExtension
     */
    protected $activityExtension;

    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * Table name for PaymentTerm
     */
    const TABLE_NAME = 'oro_payment_term';

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_4';
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
        $this->createOroPaymentTermTransportLabelTable($schema);
        $this->addOroPaymentTermTransportLabelForeignKeys($schema);

        $this->createOroPaymentTermShortLabelTable($schema);
        $this->addOroPaymentTermShortLabelForeignKeys($schema);

        if ($schema->hasTable(self::TABLE_NAME)) {
            $this->migrate($schema, $queries);

            return;
        }

        $this->createOroPaymentTermTable($schema);

        $this->activityExtension->addActivityAssociation($schema, 'oro_note', self::TABLE_NAME);

        $this->paymentTermExtension->addPaymentTermAssociation($schema, 'oro_customer', [
            'importexport' => [
                'full' => true,
                'header' => 'Payment term',
            ]
        ]);
        $this->paymentTermExtension->addPaymentTermAssociation($schema, 'oro_customer_group');
    }

    /**
     * Create table for PaymentTerm entity
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
        $this->paymentTermExtension->addPaymentTermAssociation($schema, 'oro_customer');
        $this->paymentTermExtension->addPaymentTermAssociation($schema, 'oro_customer_group');

        $this->migrateRelations($schema, $queries);

        $associationTableName = $this->activityExtension->getAssociationTableName('oro_note', self::TABLE_NAME);
        if (!$schema->hasTable($associationTableName)) {
            $updateNoteAssociationKindQuery = new UpdateNoteAssociationKindQuery(
                $schema,
                $this->activityExtension,
                $this->extendExtension,
                $this->nameGenerator
            );
            $updateNoteAssociationKindQuery->registerOldClassNameForClass(
                'Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm',
                'Oro\Bundle\PaymentBundle\Entity\PaymentTerm'
            );

            $queries->addPostQuery($updateNoteAssociationKindQuery);
        }
    }

    protected function migrateRelations(Schema $schema, QueryBag $queries)
    {
        if ($this->platform instanceof MySqlPlatform) {
            $queryAccount = <<<QUERY
UPDATE oro_customer a
JOIN oro_payment_term_to_account pta ON pta.account_id = a.id
SET a.payment_term_7c4f1e8e_id = pta.payment_term_id
WHERE a.payment_term_7c4f1e8e_id IS NULL;
QUERY;
            $queryGroup = <<<QUERY
UPDATE oro_customer_group ag
JOIN oro_payment_term_to_acc_grp ptag ON ptag.account_group_id = ag.id
SET ag.payment_term_7c4f1e8e_id = ptag.payment_term_id;
WHERE ag.payment_term_7c4f1e8e_id IS NULL;
QUERY;
        } elseif ($this->platform instanceof PostgreSqlPlatform) {
            $queryAccount = <<<QUERY
UPDATE oro_customer a
SET payment_term_7c4f1e8e_id = pta.payment_term_id
FROM oro_payment_term_to_account pta
WHERE pta.account_id = a.id AND a.payment_term_7c4f1e8e_id IS NULL;
QUERY;
            $queryGroup = <<<QUERY
UPDATE oro_customer_group ag
SET payment_term_7c4f1e8e_id = ptag.payment_term_id
FROM oro_payment_term_to_acc_grp ptag
WHERE ptag.account_group_id = ag.id AND ag.payment_term_7c4f1e8e_id IS NULL;
QUERY;
        } else {
            throw new \RuntimeException('Unsupported platform ');
        }

        $queries->addPostQuery(
            new RemoveFieldQuery('Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm', 'accounts')
        );

        $queries->addPostQuery(
            new RemoveFieldQuery('Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm', 'accountGroups')
        );

        if ($schema->hasTable('oro_payment_term_to_account')) {
            $queries->addPostQuery($queryAccount);
            $queries->addPostQuery('DROP TABLE oro_payment_term_to_account;');
        }

        if ($schema->hasTable('oro_payment_term_to_acc_grp')) {
            $queries->addPostQuery($queryGroup);
            $queries->addPostQuery('DROP TABLE oro_payment_term_to_acc_grp;');
        }
    }

    private function createOroPaymentTermTransportLabelTable(Schema $schema)
    {
        $table = $schema->createTable('oro_payment_term_trans_label');

        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);

        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addIndex(['transport_id'], 'oro_payment_term_trans_label_transport_id', []);
        $table->addUniqueIndex(['localized_value_id'], 'oro_payment_term_trans_label_localized_value_id', []);
    }

    private function createOroPaymentTermShortLabelTable(Schema $schema)
    {
        $table = $schema->createTable('oro_payment_term_short_label');

        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);

        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addIndex(['transport_id'], 'oro_payment_term_short_label_transport_id', []);
        $table->addUniqueIndex(['localized_value_id'], 'oro_payment_term_short_label_localized_value_id', []);
    }

    /**
     * @throws SchemaException
     */
    private function addOroPaymentTermTransportLabelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_payment_term_trans_label');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @throws SchemaException
     */
    private function addOroPaymentTermShortLabelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_payment_term_short_label');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }
}
