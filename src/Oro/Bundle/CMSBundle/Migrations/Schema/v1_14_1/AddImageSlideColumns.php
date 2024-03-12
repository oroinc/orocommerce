<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_14_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds new properties to Image Slide
 * - to hold images with different sizes
 * - header text
 */
class AddImageSlideColumns implements Migration, AttachmentExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    private RenameExtension $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $schema
            ->getTable('oro_cms_image_slide')
            ->addColumn(
                'header',
                'string',
                [
                    'length' => 255,
                    'notnull' => false,
                ]
            );

        $this->addSlideImageRelation($schema, 'extraLargeImage2x');
        $this->addSlideImageRelation($schema, 'extraLargeImage3x');
        $this->addSlideImageRelation($schema, 'largeImage');
        $this->addSlideImageRelation($schema, 'largeImage2x');
        $this->addSlideImageRelation($schema, 'largeImage3x');
        $this->addSlideImageRelation($schema, 'mediumImage2x');
        $this->addSlideImageRelation($schema, 'mediumImage3x');
        $this->addSlideImageRelation($schema, 'smallImage2x');
        $this->addSlideImageRelation($schema, 'smallImage3x');
    }

    public function addSlideImageRelation(Schema $schema, string $sourceColumnName): void
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_cms_image_slide',
            $sourceColumnName,
            ['attachment' => ['acl_protected' => false, 'use_dam' => true]],
            10
        );
    }
}
