<?php

namespace Oro\Bundle\VisibilityBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroVisibilityBundle implements Migration, RenameExtensionAwareInterface
{
    use MigrationConstraintTrait;

    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameTables($schema, $queries);
    }

    private function renameTables(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;
        $extension->renameTable($schema, $queries, 'oro_acc_category_visibility', 'oro_cus_category_visibility');
        $extension->renameTable($schema, $queries, 'oro_acc_grp_ctgr_visibility', 'oro_cus_grp_ctgr_visibility');
        $extension->renameTable($schema, $queries, 'oro_acc_product_visibility', 'oro_cus_product_visibility');
        $extension->renameTable($schema, $queries, 'oro_acc_grp_prod_visibility', 'oro_cus_grp_prod_visibility');
        $extension->renameTable($schema, $queries, 'oro_acc_grp_prod_vsb_resolv', 'oro_cus_grp_prod_vsb_resolv');
        $extension->renameTable($schema, $queries, 'oro_acc_prod_vsb_resolv', 'oro_cus_prod_vsb_resolv');
        $extension->renameTable($schema, $queries, 'oro_acc_grp_ctgr_vsb_resolv', 'oro_cus_grp_ctgr_vsb_resolv');
        $extension->renameTable($schema, $queries, 'oro_acc_ctgr_vsb_resolv', 'oro_cus_ctgr_vsb_resolv');
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
