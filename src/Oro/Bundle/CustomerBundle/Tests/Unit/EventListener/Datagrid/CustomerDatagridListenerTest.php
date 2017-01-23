<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\EventListener\Datagrid\CustomerDatagridListener;
use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @dbIsolation
 */
class CustomerDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var CustomerDatagridListener
     */
    protected $listener;

    /**
     * @var string
     */
    protected $entityClass = 'TestEntity';

    /**
     * @var CustomerUserProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityProvider;

    /**
     * @var CustomerRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $datagrid;

    /**
     * @var DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $datagridConfig;

    /**
     * @var array
     */
    protected $childIds = [42];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->securityProvider = $this->getMockBuilder(CustomerUserProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->datagrid = $this->createMock(DatagridInterface::class);
        $this->datagridConfig = $this->getMockBuilder(DatagridConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder(CustomerRepository::class)->disableOriginalConstructor()->getMock();
        $this->repository->expects($this->any())->method('getChildrenIds')->willReturn($this->childIds);

        $this->listener = new CustomerDatagridListener($this->securityProvider, $this->repository);
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     * @dataProvider buildBeforeFrontendQuotesProvider
     */
    public function testBuildBeforeFrontendItems(array $inputData, array $expectedData)
    {
        $this->securityProvider->expects($this->any())
            ->method('isGrantedViewLocal')
            ->with($this->entityClass)
            ->willReturn($inputData['grantedViewLocal']);
        $this->securityProvider->expects($this->any())
            ->method('isGrantedViewDeep')
            ->with($this->entityClass)
            ->willReturn($inputData['grantedViewDeep']);
        $this->securityProvider->expects($this->any())
            ->method('isGrantedViewSystem')
            ->with($this->entityClass)
            ->willReturn($inputData['grantedViewSystem']);

        $this->securityProvider->expects($this->any())
            ->method('isGrantedViewCustomerUser')
            ->with($this->entityClass)
            ->willReturn($inputData['grantedViewCustomerUser']);

        /** @var CustomerUser $user */
        $user = $inputData['user'];

        $this->securityProvider->expects($this->any())->method('getLoggedUser')->willReturn($user);

        $datagridConfig = DatagridConfiguration::create($inputData['config']);

        $event = new BuildBefore($this->datagrid, $datagridConfig);

        $this->listener->onBuildBeforeFrontendItems($event);

        $this->assertEquals($expectedData['config'], $datagridConfig->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function buildBeforeFrontendQuotesProvider()
    {
        return [
            '!CustomerUser' => [
                'input' => [
                    'user' => null,
                    'config' => $this->getConfig(),
                    'grantedViewLocal' => false,
                    'grantedViewDeep' => false,
                    'grantedViewSystem' => false,
                    'grantedViewCustomerUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(),
                ],
            ],
            'empty root options' => [
                'input' => [
                    'user' => $this->getCustomerUser(),
                    'config' => $this->getConfig(false, null, null, false),
                    'grantedViewLocal' => false,
                    'grantedViewDeep' => false,
                    'grantedViewSystem' => false,
                    'grantedViewCustomerUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(false, null, null, false),
                ],
            ],
            'empty [source][query][from]' => [
                'input' => [
                    'user' => $this->getCustomerUser(),
                    'config' => $this->getConfig(false, null, null, true, false),
                    'grantedViewLocal' => false,
                    'grantedViewDeep' => false,
                    'grantedViewSystem' => false,
                    'grantedViewCustomerUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(false, null, null, true, false),
                ],
            ],
            '!CustomerUser::VIEW_LOCAL and !Entity::VIEW_LOCAL' => [
                'input' => [
                    'user' => $this->getCustomerUser(),
                    'config' => $this->getConfig(),
                    'grantedViewLocal' => false,
                    'grantedViewDeep' => false,
                    'grantedViewSystem' => false,
                    'grantedViewCustomerUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(true),
                ],
            ],
            'CustomerUser::VIEW_LOCAL and !Entity::VIEW_LOCAL' => [
                'input' => [
                    'user' => $this->getCustomerUser(),
                    'config' => $this->getConfig(),
                    'grantedViewLocal' => false,
                    'grantedViewDeep' => false,
                    'grantedViewSystem' => false,
                    'grantedViewCustomerUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(true),
                ],
            ],
            '!CustomerUser::VIEW_LOCAL and Entity::VIEW_LOCAL' => [
                'input' => [
                    'user' => $this->getCustomerUser(3, 2),
                    'config' => $this->getConfig(),
                    'grantedViewLocal' => true,
                    'grantedViewDeep' => false,
                    'grantedViewSystem' => false,
                    'grantedViewCustomerUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(true, 2, 3),
                ],
            ],
            'CustomerUser::VIEW_LOCAL and Entity::VIEW_LOCAL' => [
                'input' => [
                    'user' => $this->getCustomerUser(4, 3),
                    'config' => $this->getConfig(true),
                    'grantedViewLocal' => true,
                    'grantedViewDeep' => false,
                    'grantedViewSystem' => false,
                    'grantedViewCustomerUser' => true,
                    'customerId' => 3
                ],
                'expected' => [
                    'config' => $this->getConfig(true, 3, 4),
                ],
            ],
            'CustomerUser::VIEW_LOCAL and Entity::VIEW_DEEP' => [
                'input' => [
                    'user' => $this->getCustomerUser(4, 3),
                    'config' => $this->getConfig(true),
                    'grantedViewLocal' => false,
                    'grantedViewDeep' => true,
                    'grantedViewSystem' => false,
                    'grantedViewCustomerUser' => true,
                    'customerId' => 3
                ],
                'expected' => [
                    'config' => $this->getConfig(true, 3, 4, true, true, true),
                ],
            ],
            'CustomerUser::VIEW_LOCAL and Entity::VIEW_SYSTEM' => [
                'input' => [
                    'user' => $this->getCustomerUser(4, 3),
                    'config' => $this->getConfig(true),
                    'grantedViewLocal' => false,
                    'grantedViewDeep' => false,
                    'grantedViewSystem' => true,
                    'grantedViewCustomerUser' => true,
                    'customerId' => 3
                ],
                'expected' => [
                    'config' => $this->getConfig(true, 3, 4, true, true, true),
                ],
            ],
        ];
    }

    /**
     * @param int $id
     * @param int $customerId
     * @return CustomerUser
     */
    protected function getCustomerUser($id = null, $customerId = null)
    {
        return $this->getEntity(
            CustomerUser::class,
            ['id' => $id, 'customer' => $this->getEntity(Customer::class, ['id' => $customerId])]
        );
    }

    /**
     * @param bool $empty
     * @param bool $customerId
     * @param bool $customerUserId
     * @param bool $customerUserOwner
     * @param bool $sourceQueryFrom
     * @param bool $withChildIds
     * @return array
     */
    protected function getConfig(
        $empty = false,
        $customerId = null,
        $customerUserId = null,
        $customerUserOwner = true,
        $sourceQueryFrom = true,
        $withChildIds = false
    ) {
        $config = [
            'options' => [],
            'source' => [
                'query' => [],
            ],
            'columns' => [],
            'sorters' => [
                'columns' => [],
            ],
            'filters' => [
                'columns' => [],
            ],
            'action_configuration' => null,
        ];

        if ($customerUserOwner) {
            $config['options']['customerUserOwner'] = [
                'customerUserColumn' => 'customerUserName',
            ];
        }

        if ($sourceQueryFrom) {
            $config['source']['query']['from'] = [
                [
                    'table' => $this->entityClass,
                    'alias' => 'tableAlias',
                ],
            ];
        }

        if (!$empty) {
            $config['columns']['customerUserName'] = true;
            $config['sorters']['columns']['customerUserName'] = true;
            $config['filters']['columns']['customerUserName'] = true;
        }

        if (null !== $customerId) {
            $config['source']['skip_acl_apply'] = true;
            $config['source']['query']['where'] = [
                'and' => [
                    sprintf(
                        '(tableAlias.customer IN (%s) OR tableAlias.customerUser = %d)',
                        implode(',', array_merge([$customerId], $withChildIds ? $this->childIds : [])),
                        $customerUserId
                    ),
                ]
            ];
        }

        return $config;
    }
}
