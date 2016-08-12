<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;

class RenameTablesAndColumns implements Migration, RenameExtensionAwareInterface
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
        $extension = $this->renameExtension;

        // notes
        $notes = $schema->getTable('oro_note');

        $notes->removeForeignKey('FK_BA066CE14F8DA267');
        $extension->renameColumn($schema, $queries, $notes, 'price_list_895c1635_id', 'price_list_9919ee5_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'orob2b_price_list',
            ['price_list_9919ee5_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'OroB2B\Bundle\NoteBundle\Entity\Note',
            'OroB2B\Bundle\PricingBundle\Entity\PriceList',
            'price_list_895c1635',
            'price_list_9919ee5',
            RelationType::MANY_TO_ONE
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
