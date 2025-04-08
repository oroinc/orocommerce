<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerRepository;
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

    #[\Override]
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

        $repository = $this->createMock(CustomerRepository::class);
        $repository->expects($this->once())
            ->method('getAssignableCustomerIds')
            ->with($this->aclHelper, ShoppingList::class)
            ->willReturn([42]);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(Customer::class)
            ->willReturn($repository);

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
                                'IDENTITY(entity.customer) IN (:customer_ids)',
                            ],
                        ],
                    ],
                    'bind_parameters' => [
                        'customer_ids',
                    ],
                ]
            ],
            $config->toArray()
        );

        $this->assertEquals(
            ['customer_ids' => [42]],
            $params->all()
        );
    }
}
