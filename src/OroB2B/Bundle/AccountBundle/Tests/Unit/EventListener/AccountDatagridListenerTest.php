<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\EventListener\AccountDatagridListener;
use OroB2B\Bundle\AccountBundle\SecurityFacade;

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
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

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
        $this->securityFacade = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $this->datagridConfig = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->listener = new AccountDatagridListener(
            $this->securityFacade
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     * @dataProvider buildBeforeFrontendQuotesProvider
     */
    public function testBuildBeforeFrontendQuotes(array $inputData, array $expectedData)
    {
        $this->securityFacade->expects($this->any())
            ->method('isGrantedViewLocal')
            ->with($this->entityClass)
            ->willReturn($inputData['grantedViewLocal'])
        ;

        $this->securityFacade->expects($this->any())
            ->method('isGrantedViewAccountUser')
            ->with($this->entityClass)
            ->willReturn($inputData['grantedViewAccountUser'])
        ;

        /* @var $account Account|\PHPUnit_Framework_MockObject_MockObject */
        $account = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Account');
        $account->expects($this->any())
            ->method('getId')
            ->willReturn($inputData['accountId'])
        ;

        /* @var $user AccountUser|\PHPUnit_Framework_MockObject_MockObject */
        $user = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountUser');
        $user->expects($this->any())
            ->method('getAccount')
            ->willReturn($account)
        ;
        $user->expects($this->any())
            ->method('getId')
            ->willReturn($inputData['accountUserId'])
        ;

        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($user)
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
            'empty root options' => [
                'input' => [
                    'config'        => $this->getConfig(false, null, null, false),
                    'accountId'     => null,
                    'accountUserId' => null,
                    'grantedViewLocal' => false,
                    'grantedViewAccountUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(false, null, null, false),
                ],
            ],
            'empty [source][query][from]' => [
                'input' => [
                    'config'        => $this->getConfig(false, null, null, true, false),
                    'accountId'     => null,
                    'accountUserId' => null,
                    'grantedViewLocal' => false,
                    'grantedViewAccountUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(false, null, null, true, false),
                ],
            ],
            '!AccountUser::VIEW_LOCAL and !Entity::VIEW_LOCAL' => [
                'input' => [
                    'config'        => $this->getConfig(),
                    'accountId'     => null,
                    'accountUserId' => null,
                    'grantedViewLocal' => false,
                    'grantedViewAccountUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(true),
                ],
            ],
            'AccountUser::VIEW_LOCAL and !Entity::VIEW_LOCAL' => [
                'input' => [
                    'config'        => $this->getConfig(),
                    'accountId'     => null,
                    'accountUserId' => null,
                    'grantedViewLocal' => false,
                    'grantedViewAccountUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(true),
                ],
            ],
            '!AccountUser::VIEW_LOCAL and Entity::VIEW_LOCAL' => [
                'input' => [
                    'config'        => $this->getConfig(),
                    'accountId'     => 2,
                    'accountUserId' => 3,
                    'grantedViewLocal' => true,
                    'grantedViewAccountUser' => false,
                ],
                'expected' => [
                    'config' => $this->getConfig(true, 2, 3),
                ],
            ],
            'AccountUser::VIEW_LOCAL and Entity::VIEW_LOCAL' => [
                'input' => [
                    'config'        => $this->getConfig(true),
                    'accountId'     => 3,
                    'accountUserId' => 4,
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
     * @param bool $empty
     * @param bool $accountId
     * @param bool $accountUserId
     * @param bool $accountUserOwner
     * @param bool $sourceQueryFrom
     * @return array
     */
    protected function getConfig($empty = false, $accountId = null, $accountUserId = null, $accountUserOwner = true, $sourceQueryFrom = true)
    {
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
                    sprintf('tableAlias.account = %d OR tableAlias.accountUser = %d', $accountId, $accountUserId),
                ]
            ];
        }

        return $config;
    }
}
