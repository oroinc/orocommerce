<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\src\OroB2B\Bundle\AccountBundle\EventListener\FrontendDatagridListener;

class FrontendDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    const USER_ID = 1;

    /**
     * @var FrontendDatagridListener
     */
    protected $listener;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenStorage;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenInterface;

    /**
     * @var array
     */
    protected $expectedDataForWorse = [
        'source' => [
            'query' => [
                'where' => [
                    'and' => [
                        '1=0'
                    ]
                ]
            ]
        ]
    ];

    protected function setUp()
    {
        $this->tokenStorage =
            $this->getMock('\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');

        $this->tokenInterface = $this->getMock('\Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->securityFacade = $this->getMockBuilder('\Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new FrontendDatagridListener($this->tokenStorage, $this->securityFacade);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->tokenStorage, $this->tokenInterface, $this->securityFacade);
    }

    /**
     * @param bool     $isGranted
     * @param null|int $user
     * @param bool     $hasToken
     * @param array    $expected
     * @dataProvider   onBuildBeforeProvider
     */
    public function testOnBuildBefore($isGranted = false, $user = null, $hasToken = true, array $expected = [])
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('orob2b_account_account_user_role_frontend_view')
            ->willReturn($isGranted);

        if ($hasToken) {
            $this->tokenStorage->expects($this->once())
                ->method('getToken')
                ->willReturn($this->tokenInterface);
            $this->mockUser($user);
        }

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals($expected, $config->toArray());
    }

    /**
     * @return array
     */
    public function onBuildBeforeProvider()
    {
        return [
            'when user have permissions' => [
                'isGranted' => true,
                'user' => static::USER_ID,
                'hasToken' => true,
                'expected' => [
                    'source' => [
                        'query' => [
                            'where' => [
                                'and' => [
                                    'role.account IN (' . static::USER_ID . ')'
                                ],
                                'or' => [
                                    'role.account IS NULL'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'when user have not permissions' => [
                'isGranted' => false,
                'user' => static::USER_ID,
                'hasToken' => true,
                'expected' => $this->expectedDataForWorse
            ],
            'when user not logged' => [
                'isGranted' => false,
                'user' => false,
                'hasToken' => true,
                'expected' => $this->expectedDataForWorse
            ],
            'when user not logged and have no token' => [
                'isGranted' => true,
                'user' => false,
                'hasToken' => false,
                'expected' => $this->expectedDataForWorse
            ]
        ];
    }

    /**
     * @param string $className
     * @param int $id
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;
        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);
        return $entity;
    }

    /**
     * @param int $userId
     */
    protected function mockUser($userId)
    {
        if ($userId) {
            $this->tokenInterface->expects($this->once())
                ->method('getUser')
                ->willReturn($this->getEntity('\OroB2B\Bundle\AccountBundle\Entity\AccountUser', $userId));
        } else {
            $this->tokenInterface->expects($this->once())
                ->method('getUser')
                ->willReturn(null);
        }
    }
}
