<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\EventListener\AccountDatagridListener;
use OroB2B\Bundle\AccountBundle\Security\AccountUserProvider;

/**
 * @dbIsolation
 */
class AccountDatagridListenerTest extends WebTestCase
{
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
     * @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $datagrid;

    /**
     * @var DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $datagridConfig;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->securityProvider = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Security\AccountUserProvider')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $this->datagridConfig = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->listener = new AccountDatagridListener(
            $this->securityProvider
        );
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
            ->willReturn($inputData['grantedViewLocal'])
        ;

        $this->securityProvider->expects($this->any())
            ->method('isGrantedViewAccountUser')
            ->with($this->entityClass)
            ->willReturn($inputData['grantedViewAccountUser'])
        ;

        $this->securityProvider->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($inputData['user'])
        ;

        $datagridConfig = DatagridConfiguration::create($inputData['config']);

        $event = new BuildBefore($this->datagrid, $datagridConfig);

        $this->listener->onBuildBeforeFrontendItems($event);

        $this->assertEquals($expectedData['config'], $datagridConfig->toArray());
    }

    /**
     * @return array
     */
    public function buildBeforeFrontendQuotesProvider()
    {
        return [
            '!AccountUser' => [
                'input' => [
                    'user' => null,
                    'config' => $this->getConfig(),
                    'grantedViewLocal' => false,
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
                    'grantedViewAccountUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(false, null, null, true, false),
                ],
            ],
            '!AccountUser::VIEW_LOCAL and !Entity::VIEW_LOCAL' => [
                'input' => [
                    'user' => $this->getAccountUser(),
                    'config' => $this->getConfig(),
                    'grantedViewLocal' => false,
                    'grantedViewAccountUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(true),
                ],
            ],
            'AccountUser::VIEW_LOCAL and !Entity::VIEW_LOCAL' => [
                'input' => [
                    'user' => $this->getAccountUser(),
                    'config' => $this->getConfig(),
                    'grantedViewLocal' => false,
                    'grantedViewAccountUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(true),
                ],
            ],
            '!AccountUser::VIEW_LOCAL and Entity::VIEW_LOCAL' => [
                'input' => [
                    'user' => $this->getAccountUser(3, 2),
                    'config' => $this->getConfig(),
                    'grantedViewLocal' => true,
                    'grantedViewAccountUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(true, 2, 3),
                ],
            ],
            'AccountUser::VIEW_LOCAL and Entity::VIEW_LOCAL' => [
                'input' => [
                    'user' => $this->getAccountUser(4, 3),
                    'config' => $this->getConfig(true),
                    'grantedViewLocal' => true,
                    'grantedViewAccountUser' => true,
                ],
                'expected' => [
                    'config' => $this->getConfig(true, 3, 4),
                ],
            ],
        ];
    }

    /**
     * @param int $id
     * @param int $accountId
     * @return AccountUser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAccountUser($id = null, $accountId = null)
    {
        /* @var $account Account|\PHPUnit_Framework_MockObject_MockObject */
        $account = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Account');
        $account->expects($this->any())
            ->method('getId')
            ->willReturn($accountId)
        ;

        /* @var $user AccountUser|\PHPUnit_Framework_MockObject_MockObject */
        $user = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountUser');
        $user->expects($this->any())
            ->method('getAccount')
            ->willReturn($account)
        ;
        $user->expects($this->any())
            ->method('getId')
            ->willReturn($id)
        ;

        return $user;
    }

    /**
     * @param bool $empty
     * @param bool $accountId
     * @param bool $accountUserId
     * @param bool $accountUserOwner
     * @param bool $sourceQueryFrom
     * @return array
     */
    protected function getConfig(
        $empty = false,
        $accountId = null,
        $accountUserId = null,
        $accountUserOwner = true,
        $sourceQueryFrom = true
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
            $config['options']['skip_acl_check'] = true;
            $config['source']['query']['where'] = [
                'and' => [
                    sprintf('(tableAlias.account = %d OR tableAlias.accountUser = %d)', $accountId, $accountUserId),
                ]
            ];
        }

        return $config;
    }
}
