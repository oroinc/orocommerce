<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Schema;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendNameGeneratorAwareTrait;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
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
    use PaymentTermExtensionAwareTrait;
    use DatabasePlatformAwareTrait;
    use RenameExtensionAwareTrait;
    use ActivityExtensionAwareTrait;
    use ExtendNameGeneratorAwareTrait;
    use ExtendExtensionAwareTrait;
    use UpdateExtendRelationTrait;

    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_4';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroPaymentTermTransportLabelTable($schema);
        $this->createOroPaymentTermShortLabelTable($schema);

        $this->addOroPaymentTermTransportLabelForeignKeys($schema);
        $this->addOroPaymentTermShortLabelForeignKeys($schema);

        if ($schema->hasTable('oro_payment_term')) {
            $this->migrate($schema, $queries);

            return;
        }

        $this->createOroPaymentTermTable($schema);

        $this->activityExtension->addActivityAssociation($schema, 'oro_note', 'oro_payment_term');

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
    private function createOroPaymentTermTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_payment_term');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('label', 'string');
        $table->setPrimaryKey(['id']);
    }

    private function migrate(Schema $schema, QueryBag $queries): void
    {
        $this->paymentTermExtension->addPaymentTermAssociation($schema, 'oro_customer');
        $this->paymentTermExtension->addPaymentTermAssociation($schema, 'oro_customer_group');

        $this->migrateRelations($schema, $queries);

        $associationTableName = $this->activityExtension->getAssociationTableName('oro_note', 'oro_payment_term');
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

    private function migrateRelations(Schema $schema, QueryBag $queries): void
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

    private function createOroPaymentTermTransportLabelTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_payment_term_trans_label');
        $table->addColumn('transport_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addIndex(['transport_id'], 'oro_payment_term_trans_label_transport_id');
        $table->addUniqueIndex(['localized_value_id'], 'oro_payment_term_trans_label_localized_value_id');
    }

    private function createOroPaymentTermShortLabelTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_payment_term_short_label');
        $table->addColumn('transport_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addIndex(['transport_id'], 'oro_payment_term_short_label_transport_id');
        $table->addUniqueIndex(['localized_value_id'], 'oro_payment_term_short_label_localized_value_id');
    }

    private function addOroPaymentTermTransportLabelForeignKeys(Schema $schema): void
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

    private function addOroPaymentTermShortLabelForeignKeys(Schema $schema): void
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
}
