<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v6_1_6_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Override;

class AddOrderPdfDocumentsRelation implements Migration
{
    #[Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroOrderPdfDocumentTable($schema);
    }

    private function createOroOrderPdfDocumentTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_order_pdf_document');

        $table->addColumn('order_id', 'integer');
        $table->addColumn('pdf_document_id', 'integer');

        $table->setPrimaryKey(['order_id', 'pdf_document_id']);
        $table->addUniqueIndex(['pdf_document_id'], 'oro_order_pdf_document_uidx');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order'),
            ['order_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'oro_order_pdf_document_order_id_fk'
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_pdf_generator_pdf_document'),
            ['pdf_document_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'oro_order_pdf_document_pdf_document_id_fk'
        );
    }
}
