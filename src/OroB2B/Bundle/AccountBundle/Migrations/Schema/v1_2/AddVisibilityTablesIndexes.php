<?php

namespace Oro\Bundle\AccountBundle\Migrations\Schema\v1_2;

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
        $this->addOroCategoryVisibilityTableIndex($schema);
        $this->addOroAccountGroupCategoryVisibilityTableIndex($schema);
        $this->addOroAccountCategoryVisibilityTableIndex($schema);
        $this->addOroProductVisibilityTableIndex($schema);
        $this->addOroAccountProductVisibilityTableIndex($schema);
        $this->addOroAccountGroupProductVisibilityTableIndex($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroCategoryVisibilityTableIndex(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addUniqueIndex(['category_id'], 'orob2b_ctgr_vis_uidx');
    }

    /**
     * @param Schema $schema
     */
    protected function addOroAccountGroupCategoryVisibilityTableIndex(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addUniqueIndex(['category_id', 'account_group_id'], 'orob2b_acc_grp_ctgr_vis_uidx');
    }

    /**
     * @param Schema $schema
     */
    protected function addOroAccountCategoryVisibilityTableIndex(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addUniqueIndex(['category_id', 'account_id'], 'orob2b_acc_ctgr_vis_uidx');
    }

    /**
     * @param Schema $schema
     */
    protected function addOroProductVisibilityTableIndex(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addUniqueIndex(['website_id', 'product_id'], 'orob2b_prod_vis_uidx');
    }

    /**
     * @param Schema $schema
     */
    protected function addOroAccountProductVisibilityTableIndex(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addUniqueIndex(['website_id', 'product_id', 'account_id'], 'orob2b_acc_prod_vis_uidx');
    }

    /**
     * @param Schema $schema
     */
    protected function addOroAccountGroupProductVisibilityTableIndex(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addUniqueIndex(['website_id', 'product_id', 'account_group_id'], 'orob2b_acc_grp_prod_vis_uidx');
    }
}
