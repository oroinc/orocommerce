<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\EventListener;

use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

use OroB2B\Bundle\SaleBundle\EventListener\DatagridListener;

/**
 * @dbIsolation
 */
class DatagridListenerTest extends WebTestCase
{
    /**
     * @var DatagridListener
     */
    protected $listener;

    /**
     * @var string
     */
    protected $quoteClass = 'OroB2B\Bundle\SaleBundle\Entity\Quote';

    /**
     * @var string
     */
    protected $accountUserClass = 'OroB2B\Bundle\AccountBundle\Entity\AccountUser';

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
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $this->datagridConfig = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->listener = new DatagridListener(
            $this->quoteClass,
            $this->accountUserClass,
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
            ->method('isGrantedClassPermission')
            ->with($inputData['permission']['permission'], $inputData['permission']['class'])
            ->willReturn($inputData['permission']['return'])
        ;

        $this->securityFacade->expects($this->any())
            ->method('isGrantedClassMask')
            ->with($inputData['mask']['mask'], $inputData['mask']['class'])
            ->willReturn($inputData['mask']['return'])
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

        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($user)
        ;

        $datagridConfig = DatagridConfiguration::create($inputData['config']);

        $event = new BuildBefore($this->datagrid, $datagridConfig);

        $this->listener->onBuildBeforeFrontendQuotes($event);

        $this->assertEquals($expectedData['config'], $datagridConfig->toArray());
    }

    /**
     * @return array
     */
    public function buildBeforeFrontendQuotesProvider()
    {
        return [
            '!AccountUser::VIEW_LOCAL and !Quote::VIEW_LOCAL' => [
                'input' => [
                    'permission' => [
                        'permission'    => BasicPermissionMap::PERMISSION_VIEW,
                        'class'         => $this->accountUserClass,
                        'return'        => false,
                    ],
                    'mask' => [
                        'mask'    => EntityMaskBuilder::MASK_VIEW_LOCAL,
                        'class'   => $this->quoteClass,
                        'return'  => false,
                    ],
                    'config'    => $this->getConfig(),
                    'accountId' => null,
                ],
                'expected' => [
                    'config' => $this->getConfig(true),
                ],
            ],
            'AccountUser::VIEW_LOCAL and !Quote::VIEW_LOCAL' => [
                'input' => [
                    'permission' => [
                        'permission'    => BasicPermissionMap::PERMISSION_VIEW,
                        'class'         => $this->accountUserClass,
                        'return'        => false,
                    ],
                    'mask' => [
                        'mask'    => EntityMaskBuilder::MASK_VIEW_LOCAL,
                        'class'   => $this->quoteClass,
                        'return'  => false,
                    ],
                    'accountId' => null,
                    'config'    => $this->getConfig(),
                ],
                'expected' => [
                    'config' => $this->getConfig(true),
                ],
            ],
            '!AccountUser::VIEW_LOCAL and Quote::VIEW_LOCAL' => [
                'input' => [
                    'permission' => [
                        'permission'    => BasicPermissionMap::PERMISSION_VIEW,
                        'class'         => $this->accountUserClass,
                        'return'        => false,
                    ],
                    'mask' => [
                        'mask'    => EntityMaskBuilder::MASK_VIEW_LOCAL,
                        'class'   => $this->quoteClass,
                        'return'  => true,
                    ],
                    'accountId' => 2,
                    'config'    => $this->getConfig(),
                ],
                'expected' => [
                    'config' => $this->getConfig(true, 2),
                ],
            ],
            'AccountUser::VIEW_LOCAL and Quote::VIEW_LOCAL' => [
                'input' => [
                    'permission' => [
                        'permission'    => BasicPermissionMap::PERMISSION_VIEW,
                        'class'         => $this->accountUserClass,
                        'return'        => true,
                    ],
                    'mask' => [
                        'mask'    => EntityMaskBuilder::MASK_VIEW_LOCAL,
                        'class'   => $this->quoteClass,
                        'return'  => true,
                    ],
                    'accountId' => 3,
                    'config'    => $this->getConfig(true),
                ],
                'expected' => [
                    'config' => $this->getConfig(true, 3),
                ],
            ],
        ];
    }

    /**
     * @param bool $empty
     * @param int $accountId
     * @return array
     */
    protected function getConfig($empty = false, $accountId = null)
    {
        $config = [
            'columns' => [],
            'sorters' => [
                'columns' => [],
            ],
            'filters' => [
                'columns' => [],
            ],
        ];

        if (!$empty) {
            $config['columns']['accountUserName'] = true;
            $config['sorters']['columns']['accountUserName'] = true;
            $config['filters']['columns']['accountUserName'] = true;
        }

        if (null !== $accountId) {
            $config = array_merge(
                $config,
                [
                    'options' => [
                        'skip_acl_check' => true,
                    ],
                ],
                [
                    'source' => [
                        'query' => [
                            'where' => [
                                'and' => [
                                    'quote.account = ' . $accountId,
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }

        return $config;
    }
}
