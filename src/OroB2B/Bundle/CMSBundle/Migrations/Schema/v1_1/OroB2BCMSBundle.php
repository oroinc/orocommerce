<?php

namespace OroB2B\Bundle\CMSBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;

use OroB2B\Bundle\CMSBundle\Migrations\Schema\OroB2BCMSBundleInstaller;

class OroB2BCMSBundle implements Migration, AttachmentExtensionAwareInterface
{
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
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BCmsLoginPageTable($schema);
        $this->addImageAssociations($schema);
    }

    /**
     * Create orob2b_cms_login_page table
     *
     * @param Schema $schema
     */
    protected function createOrob2BCmsLoginPageTable(Schema $schema)
    {
        $table = $schema->createTable(OroB2BCMSBundleInstaller::CMS_LOGIN_PAGE_TABLE);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('top_content', 'text', []);
        $table->addColumn('bottom_content', 'text', []);
        $table->addColumn('css', 'text', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addImageAssociations(Schema $schema)
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            OroB2BCMSBundleInstaller::CMS_LOGIN_PAGE_TABLE,
            'logoImage',
            [],
            OroB2BCMSBundleInstaller::MAX_LOGO_IMAGE_SIZE_IN_MB
        );

        $this->attachmentExtension->addImageRelation(
            $schema,
            OroB2BCMSBundleInstaller::CMS_LOGIN_PAGE_TABLE,
            'backgroundImage',
            [],
            OroB2BCMSBundleInstaller::MAX_BACKGROUND_IMAGE_SIZE_IN_MB
        );
    }
}
