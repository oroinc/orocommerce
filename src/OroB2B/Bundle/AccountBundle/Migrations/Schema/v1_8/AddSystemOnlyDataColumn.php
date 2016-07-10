<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;

use OroB2B\Bundle\AccountBundle\Migrations\Schema\LoadRolesDataTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddSystemOnlyDataColumn implements Migration, ContainerAwareInterface
{
    use LoadRolesDataTrait;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_account_user_role');
        $table->addColumn('non_public', 'boolean', ['notnull' => false]);

        $this->updateAccountUserRoles($queries);
    }

    /**
     * @param QueryBag $queries
     */
    public function updateAccountUserRoles(QueryBag $queries)
    {
        $roleData = $this->loadRolesData();

        foreach ($roleData as $roleName => $roleConfigData) {
            if (isset($roleConfigData['non_public']) && $roleConfigData['non_public']) {
                $queries->addPostQuery(
                    'update orob2b_account_user_role set non_public = true where label = \''
                    .$roleConfigData['label']
                    .'\''
                );
            }
        }
    }

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

}
