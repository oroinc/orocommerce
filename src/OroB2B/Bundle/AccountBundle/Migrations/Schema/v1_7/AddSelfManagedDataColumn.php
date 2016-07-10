<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\AccountBundle\Migrations\Schema\LoadRolesDataTrait;

class AddSelfManagedDataColumn implements Migration, ContainerAwareInterface
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
        $table->addColumn('self_managed', 'boolean', ['notnull' => false]);

        $this->updateAccountUserRoles($queries);
    }

    /**
     * @param QueryBag $queries
     */
    public function updateAccountUserRoles(QueryBag $queries)
    {
        $roleData = $this->loadRolesData();

        foreach ($roleData as $roleName => $roleConfigData) {
            if (isset($roleConfigData['self_managed']) && $roleConfigData['self_managed']) {
                $queries->addPostQuery(
                    'update orob2b_account_user_role set self_managed = true where label = \''
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
