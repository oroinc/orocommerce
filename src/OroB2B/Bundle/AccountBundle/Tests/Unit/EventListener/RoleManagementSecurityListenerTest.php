<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;

class RoleManagementSecurityListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider onKernelControllerDataProvider
     */
    public function testOnKernelController($route, $grantedPrivileges, $isSelfManaged, $expectedAllowance)
    {
        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->setMethods(['isGranted'])
            ->disableOriginalConstructor()
            ->getMock();

        $securityFacade->expects($this->any())
            ->method('isGranted')
            ->will($this->returnCallback(function ($privilege) use ($grantedPrivileges) {
                return in_array($privilege, $grantedPrivileges);
            }));

        $role = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountUserRole');

        $role->expects($this->any())
            ->method('isSelfManaged')
            ->will($this->returnValue($isSelfManaged));

        $role->expects($this->any())
            ->method('isPredefined')
            ->willReturn(true);

        $accountUserRoleRepository = $this->getMockBuilder(
            'OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository'
        )
            ->setMethods(['find'])
            ->disableOriginalConstructor()
            ->getMock();

        $accountUserRoleRepository->expects($this->any())
            ->method('find')
            ->will($this->returnValue($role));

        $request = new Request();
        $request->attributes->set('_route', $route);
        $request->attributes->set('_route_params', [
            'id' => 1
        ]);

        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\FilterControllerEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        if (!$expectedAllowance) {
            $this->setExpectedException('Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException');
        }

        $listener = new RoleManagementSecurityListener($securityFacade, $accountUserRoleRepository);
        $listener->onKernelController($event);
    }

    public function onKernelControllerDataProvider()
    {
        return [
            [
                'route' => 'orob2b_account_frontend_account_user_role_view',
                'grantedPrivileges' => [
                    'orob2b_account_frontend_account_user_role_view'
                ],
                'isSelfManaged' => true,
                'expectedAllowance' => true
            ],
            [
                'route' => 'orob2b_account_frontend_account_user_role_view',
                'grantedPrivileges' => [
                    'orob2b_account_frontend_account_user_role_view'
                ],
                'isSelfManaged' => false,
                'expectedAllowance' => false
            ],
            [
                'route' => 'orob2b_account_frontend_account_user_role_update',
                'grantedPrivileges' => [
                    'orob2b_account_frontend_account_user_role_create'
                ],
                'isSelfManaged' => true,
                'expectedAllowance' => true
            ],
            [
                'route' => 'orob2b_account_frontend_account_user_role_update',
                'grantedPrivileges' => [
                    'orob2b_account_frontend_account_user_role_create'
                ],
                'isSelfManaged' => false,
                'expectedAllowance' => false
            ],
            [
                'route' => 'orob2b_account_frontend_account_user_role_view',
                'grantedPrivileges' => [
                ],
                'isSelfManaged' => true,
                'expectedAllowance' => false
            ],
            [
                'route' => 'orob2b_account_frontend_account_user_role_update',
                'grantedPrivileges' => [
                ],
                'isSelfManaged' => true,
                'expectedAllowance' => false
            ]
        ];
    }
}
