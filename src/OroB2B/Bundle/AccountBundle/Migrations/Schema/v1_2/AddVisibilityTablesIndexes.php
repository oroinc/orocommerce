<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddVisibilityTablesIndexes implements Migration
{
    const ORO_B2B_PRODUCT_VISIBILITY_RESOLVED = 'orob2b_prod_vsb_resolv';
    const ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_RESOLVED = 'orob2b_acc_grp_prod_vsb_resolv';
    const ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_RESOLVED = 'orob2b_acc_prod_vsb_resolv';

    const ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME = 'orob2b_category_visibility';
    const ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME = 'orob2b_acc_category_visibility';
    const ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME = 'orob2b_acc_grp_ctgr_visibility';

    const ORO_B2B_PRODUCT_VISIBILITY_TABLE_NAME = 'orob2b_product_visibility';
    const ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME = 'orob2b_acc_product_visibility';
    const ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME = 'orob2b_acc_grp_prod_visibility';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addOroB2BCategoryVisibilityTableIndex($schema);
        $this->addOroB2BAccountGroupCategoryVisibilityTableIndex($schema);
        $this->addOroB2BAccountCategoryVisibilityTableIndex($schema);
        $this->addOroB2BProductVisibilityTableIndex($schema);
        $this->addOroB2BAccountProductVisibilityTableIndex($schema);
        $this->addOroB2BAccountGroupProductVisibilityTableIndex($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BCategoryVisibilityTableIndex(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addUniqueIndex(['category_id'], 'orob2b_ctgr_vis_uidx');
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BAccountGroupCategoryVisibilityTableIndex(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addUniqueIndex(['category_id', 'account_group_id'], 'orob2b_acc_grp_ctgr_vis_uidx');
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BAccountCategoryVisibilityTableIndex(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addUniqueIndex(['category_id', 'account_id'], 'orob2b_acc_ctgr_vis_uidx');
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BProductVisibilityTableIndex(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addUniqueIndex(['website_id', 'product_id'], 'orob2b_prod_vis_uidx');
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BAccountProductVisibilityTableIndex(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addUniqueIndex(['website_id', 'product_id', 'account_id'], 'orob2b_acc_prod_vis_uidx');
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BAccountGroupProductVisibilityTableIndex(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addUniqueIndex(['website_id', 'product_id', 'account_group_id'], 'orob2b_acc_grp_prod_vis_uidx');
    }
}
