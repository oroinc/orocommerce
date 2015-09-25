<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BAccountBundle implements Migration, ExtendExtensionAwareInterface
{
    const ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME = 'orob2b_category_visibility';
    const ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME = 'orob2b_acc_category_visibility';
    const ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME = 'orob2b_acc_grp_ctgr_visibility';

    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables update **/
        $this->updateOroB2BCategoryVisibilityTable($schema);
        $this->updateOroB2BAccountCategoryVisibilityTable($schema);
        $this->updateOroB2BAccountGroupCategoryVisibilityTable($schema);
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * Update orob2b_category_visibility table
     *
     * @param Schema $schema
     */
    protected function updateOroB2BCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('visibility', 'string', ['length' => 255]);
    }

    /**
     * Update orob2b_acc_category_visibility table
     *
     * @param Schema $schema
     */
    protected function updateOroB2BAccountCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('visibility', 'string', ['length' => 255]);
    }

    /**
     * Update orob2b_acc_grp_ctgr_visibility table
     *
     * @param Schema $schema
     */
    protected function updateOroB2BAccountGroupCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('visibility', 'string', ['length' => 255]);
    }
}
