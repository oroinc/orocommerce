<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;
use OroB2B\Bundle\CustomerBundle\Migrations\Schema\v1_0\OroB2BCustomerBundle as OroB2BCustomerBundle10;
use OroB2B\Bundle\CustomerBundle\Migrations\Schema\v1_0\OroB2BCustomerExtensions as OroB2BCustomerExtensions10;

class OroB2BCustomerBundleInstaller implements
    Installation,
    NoteExtensionAwareInterface,
    AttachmentExtensionAwareInterface
{

    /**
     * @var NoteExtension
     */
    protected $noteExtension;

    /**
     * @var AttachmentExtension
     */
    protected $attachmentExtension;

    /**
     * Sets the AttachmentExtension
     *
     * @param AttachmentExtension $attachmentExtension
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
    }

    /**
     * Sets the NoteExtension
     *
     * @param NoteExtension $noteExtension
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }


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
    public function up(Schema $schema, QueryBag $queries)
    {
        $migration = new OroB2BCustomerBundle10();
        $migration->up($schema, $queries);

        $migrationExtension = new OroB2BCustomerExtensions10();
        $migrationExtension->setAttachmentExtension($this->attachmentExtension);
        $migrationExtension->setNoteExtension($this->noteExtension);
        $migrationExtension->up($schema, $queries);
    }
}
