<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Migration\RemoveEntityConfigEntityValueQuery;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareInterface;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareTrait;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OroPaymentTermBundleInstaller implements
    Installation,
    NoteExtensionAwareInterface,
    PaymentTermExtensionAwareInterface,
    DatabasePlatformAwareInterface,
    RenameExtensionAwareInterface,
    ContainerAwareInterface
{
    use PaymentTermExtensionAwareTrait, ContainerAwareTrait, UpdateExtendRelationTrait;

    /** @var AbstractPlatform */
    protected $platform;

    /** @var RenameExtension */
    protected $renameExtension;

    /**
     * Table name for PaymentTerm
     */
    const TABLE_NAME = 'oro_payment_term';
    const PAYMENT_TERM_TO_ACCOUNT_TABLE = 'oro_payment_term_to_account';
    const PAYMENT_TERM_TO_ACCOUNT_GROUP_TABLE = 'oro_payment_term_to_acc_grp';
    const ACCOUNT_TABLE = 'oro_account';
    const ACCOUNT_GROUP_TABLE = 'oro_account_group';

    /**
     * @var NoteExtension
     */
    protected $noteExtension;

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
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
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

        $this->noteExtension->addNoteAssociation($schema, self::TABLE_NAME);

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
        } else {
            throw new \RuntimeException('Unsupported platform ');
        }

        $queries->addPostQuery(
            new RemoveEntityConfigEntityValueQuery('Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm', 'accounts')
        );

        $queries->addPostQuery(
            new RemoveEntityConfigEntityValueQuery('Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm', 'accountGroups')
        );

        $queries->addPostQuery($queryAccount);
        $queries->addPostQuery($queryGroup);
        $queries->addPostQuery('DROP TABLE oro_payment_term_to_account;');
        $queries->addPostQuery('DROP TABLE oro_payment_term_to_acc_grp;');
    }
}
