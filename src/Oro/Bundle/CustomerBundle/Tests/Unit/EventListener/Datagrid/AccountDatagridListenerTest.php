<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\CustomerBundle\Entity\Repository\AccountRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\EventListener\Datagrid\AccountDatagridListener;
use Oro\Bundle\CustomerBundle\Security\AccountUserProvider;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @dbIsolation
 */
class AccountDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var AccountDatagridListener
     */
    protected $listener;

    /**
     * @var string
     */
    protected $entityClass = 'TestEntity';

    /**
     * @var AccountUserProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityProvider;

    /**
     * @var AccountRepository|\PHPUnit_Framework_MockObject_MockObject
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
        $this->securityProvider = $this->getMockBuilder(AccountUserProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->datagrid = $this->createMock(DatagridInterface::class);
        $this->datagridConfig = $this->getMockBuilder(DatagridConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder(AccountRepository::class)->disableOriginalConstructor()->getMock();
        $this->repository->expects($this->any())->method('getChildrenIds')->willReturn($this->childIds);

        $this->listener = new AccountDatagridListener($this->securityProvider, $this->repository);
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
            ->method('isGrantedViewAccountUser')
            ->with($this->entityClass)
            ->willReturn($inputData['grantedViewAccountUser']);

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
                    'grantedViewAccountUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(),
                ],
            ],
            'empty root options' => [
                'input' => [
                    'user' => $this->getAccountUser(),
                    'config' => $this->getConfig(false, null, null, false),
                    'grantedViewLocal' => false,
                    'grantedViewDeep' => false,
                    'grantedViewSystem' => false,
                    'grantedViewAccountUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(false, null, null, false),
                ],
            ],
            'empty [source][query][from]' => [
                'input' => [
                    'user' => $this->getAccountUser(),
                    'config' => $this->getConfig(false, null, null, true, false),
                    'grantedViewLocal' => false,
                    'grantedViewDeep' => false,
                    'grantedViewSystem' => false,
                    'grantedViewAccountUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(false, null, null, true, false),
                ],
            ],
            '!CustomerUser::VIEW_LOCAL and !Entity::VIEW_LOCAL' => [
                'input' => [
                    'user' => $this->getAccountUser(),
                    'config' => $this->getConfig(),
                    'grantedViewLocal' => false,
                    'grantedViewDeep' => false,
                    'grantedViewSystem' => false,
                    'grantedViewAccountUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(true),
                ],
            ],
            'CustomerUser::VIEW_LOCAL and !Entity::VIEW_LOCAL' => [
                'input' => [
                    'user' => $this->getAccountUser(),
                    'config' => $this->getConfig(),
                    'grantedViewLocal' => false,
                    'grantedViewDeep' => false,
                    'grantedViewSystem' => false,
                    'grantedViewAccountUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(true),
                ],
            ],
            '!CustomerUser::VIEW_LOCAL and Entity::VIEW_LOCAL' => [
                'input' => [
                    'user' => $this->getAccountUser(3, 2),
                    'config' => $this->getConfig(),
                    'grantedViewLocal' => true,
                    'grantedViewDeep' => false,
                    'grantedViewSystem' => false,
                    'grantedViewAccountUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(true, 2, 3),
                ],
            ],
            'CustomerUser::VIEW_LOCAL and Entity::VIEW_LOCAL' => [
                'input' => [
                    'user' => $this->getAccountUser(4, 3),
                    'config' => $this->getConfig(true),
                    'grantedViewLocal' => true,
                    'grantedViewDeep' => false,
                    'grantedViewSystem' => false,
                    'grantedViewAccountUser' => true,
                    'customerId' => 3
                ],
                'expected' => [
                    'config' => $this->getConfig(true, 3, 4),
                ],
            ],
            'CustomerUser::VIEW_LOCAL and Entity::VIEW_DEEP' => [
                'input' => [
                    'user' => $this->getAccountUser(4, 3),
                    'config' => $this->getConfig(true),
                    'grantedViewLocal' => false,
                    'grantedViewDeep' => true,
                    'grantedViewSystem' => false,
                    'grantedViewAccountUser' => true,
                    'customerId' => 3
                ],
                'expected' => [
                    'config' => $this->getConfig(true, 3, 4, true, true, true),
                ],
            ],
            'CustomerUser::VIEW_LOCAL and Entity::VIEW_SYSTEM' => [
                'input' => [
                    'user' => $this->getAccountUser(4, 3),
                    'config' => $this->getConfig(true),
                    'grantedViewLocal' => false,
                    'grantedViewDeep' => false,
                    'grantedViewSystem' => true,
                    'grantedViewAccountUser' => true,
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
    protected function getAccountUser($id = null, $customerId = null)
    {
        return $this->getEntity(
            CustomerUser::class,
            ['id' => $id, 'account' => $this->getEntity(Account::class, ['id' => $customerId])]
        );
    }

    /**
     * @param bool $empty
     * @param bool $accountId
     * @param bool $accountUserId
     * @param bool $accountUserOwner
     * @param bool $sourceQueryFrom
     * @param bool $withChildIds
     * @return array
     */
    protected function getConfig(
        $empty = false,
        $accountId = null,
        $accountUserId = null,
        $accountUserOwner = true,
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

        if ($accountUserOwner) {
            $config['options']['accountUserOwner'] = [
                'accountUserColumn' => 'accountUserName',
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
            $config['columns']['accountUserName'] = true;
            $config['sorters']['columns']['accountUserName'] = true;
            $config['filters']['columns']['accountUserName'] = true;
        }

        if (null !== $accountId) {
            $config['source']['skip_acl_apply'] = true;
            $config['source']['query']['where'] = [
                'and' => [
                    sprintf(
                        '(tableAlias.account IN (%s) OR tableAlias.accountUser = %d)',
                        implode(',', array_merge([$accountId], $withChildIds ? $this->childIds : [])),
                        $accountUserId
                    ),
                ]
            ];
        }

        return $config;
    }
}
