<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_25_1;

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
        $this->attachmentExtension->addMultiFileRelation(
            $schema,
            'oro_order',
            'documents',
            [
                'attachment' => ['file_applications' => ['default', 'commerce']],
                'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM]
            ],
            2
        );
    }
}
