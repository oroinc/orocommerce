<?php

namespace OroB2B\Bundle\PaymentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BPaymentBundleInstaller implements Installation, NoteExtensionAwareInterface
{
    /**
     * Table name for PaymentTerm
     */
    const TABLE_NAME = 'orob2b_payment_term';

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

    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroB2BPaymentTermTable($schema);
        $this->addNoteAssociations($schema);
    }

    /**
     * Create table for PaymentTerm entity
     *
     * @param Schema $schema
     */
    protected function createOroB2BPaymentTermTable(Schema $schema)
    {
        $table = $schema->createTable(self::TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('label', 'string');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Enable notes for PaymentTerm entity
     *
     * @param Schema $schema
     */
    protected function addNoteAssociations(Schema $schema)
    {
        $this->noteExtension->addNoteAssociation($schema, self::TABLE_NAME);
    }
}
