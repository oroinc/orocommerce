<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Datagrid\EventListener\FrontendShoppingListAssignGridEventListener;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class FrontendShoppingListAssignGridEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var FrontendShoppingListAssignGridEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->listener = new FrontendShoppingListAssignGridEventListener($this->registry, $this->aclHelper);
    }

    public function testOnBuildBefore(): void
    {
        $params = new ParameterBag();

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $config = DatagridConfiguration::createNamed('test-grid', []);
        $config->offsetSetByPath(OrmQueryConfiguration::FROM_PATH, [['alias' => 'entity']]);

        $repository = $this->createMock(CustomerUserRepository::class);
        $repository->expects($this->once())
            ->method('getAssignableCustomerUserIds')
            ->with($this->aclHelper, ShoppingList::class)
            ->willReturn([42]);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(CustomerUser::class)
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CustomerUser::class)
            ->willReturn($manager);

        $this->listener->onBuildBefore(new BuildBefore($datagrid, $config));

        $this->assertEquals(
            [
                'name' => 'test-grid',
                'source' => [
                    'query' => [
                        'from' => [
                            [
                                'alias' => 'entity'
                            ],
                        ],
                        'where' => [
                            'and' => [
                                'entity.id IN (:customer_user_ids)',
                            ],
                        ],
                    ],
                    'bind_parameters' => [
                        'customer_user_ids',
                    ],
                ]
            ],
            $config->toArray()
        );

        $this->assertEquals(
            ['customer_user_ids' => [42]],
            $params->all()
        );
    }
}
