<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCatalogBundle implements Migration, AttachmentExtensionAwareInterface
{
    const ORO_B2B_CATALOG_CATEGORY_TABLE_NAME = 'orob2b_catalog_category';
    const MAX_CATEGORY_IMAGE_SIZE_IN_MB = 10;
    const THUMBNAIL_WIDTH_SIZE_IN_PX = 100;
    const THUMBNAIL_HEIGHT_SIZE_IN_PX = 100;

    /** @var AttachmentExtension */
    protected $attachmentExtension;

    /**
     * {@inheritdoc}
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addCategoryImageAssociation($schema, 'largeImage');
        $this->addCategoryImageAssociation($schema, 'smallImage');
    }

    /**
     * @param Schema $schema
     * @param $fieldName
     */
    public function addCategoryImageAssociation(Schema $schema, $fieldName)
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            self::ORO_B2B_CATALOG_CATEGORY_TABLE_NAME,
            $fieldName,
            [],
            self::MAX_CATEGORY_IMAGE_SIZE_IN_MB,
            self::THUMBNAIL_WIDTH_SIZE_IN_PX,
            self::THUMBNAIL_HEIGHT_SIZE_IN_PX
        );
    }
}
