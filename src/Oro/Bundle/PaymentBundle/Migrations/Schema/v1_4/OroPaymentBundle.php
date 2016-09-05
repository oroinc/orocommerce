<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\FrontendBundle\Migration\UpdateClassNamesQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;

class OroPaymentBundle implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_payment_status');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_identifier', 'integer', []);
        $table->addColumn('payment_status', 'string', ['length' => 255]);
        $table->addUniqueIndex(['entity_class', 'entity_identifier'], 'oro_payment_status_unique');
        $table->setPrimaryKey(['id']);

        $extension = $this->renameExtension;

        // notes
        $notes = $schema->getTable('oro_note');

        $notes->removeForeignKey('FK_BA066CE1C77ACA7D');
        $extension->renameColumn($schema, $queries, $notes, 'payment_term_5f8a1ef5_id', 'payment_term_3dd15035_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'orob2b_payment_term',
            ['payment_term_3dd15035_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\PaymentBundle\Entity\PaymentTerm',
            'payment_term_5f8a1ef5',
            'payment_term_3dd15035',
            RelationType::MANY_TO_ONE
        ));

        // entity tables
        $extension->renameTable($schema, $queries, 'orob2b_payment_term', 'oro_payment_term');
        $extension->renameTable($schema, $queries, 'orob2b_payment_term_to_acc_grp', 'oro_payment_term_to_acc_grp');
        $extension->renameTable($schema, $queries, 'orob2b_payment_term_to_account', 'oro_payment_term_to_account');
        $extension->renameTable($schema, $queries, 'orob2b_payment_transaction', 'oro_payment_transaction');

        // indexes
        $schema->getTable('orob2b_payment_transaction')->dropIndex('orob2b_pay_trans_access_uidx');

        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_payment_transaction',
            ['access_identifier', 'access_token'],
            'oro_pay_trans_access_uidx'
        );

        // fix entity names in DB
        $queries->addQuery(new UpdateClassNamesQuery('oro_payment_transaction', 'entity_class'));
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
