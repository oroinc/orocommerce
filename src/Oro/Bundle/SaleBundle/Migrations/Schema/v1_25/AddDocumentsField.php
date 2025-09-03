<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_25;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddDocumentsField implements Migration, AttachmentExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if ($schema->getTable('oro_sale_quote')->hasColumn('documents')) {
            return;
        }

        $this->attachmentExtension->addMultiFileRelation(
            $schema,
            'oro_sale_quote',
            'documents',
            [
                'attachment' => ['file_applications' => ['default', 'commerce']],
                'email' => ['available_in_template' => true],
                'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM],
            ],
            2
        );
    }
}
