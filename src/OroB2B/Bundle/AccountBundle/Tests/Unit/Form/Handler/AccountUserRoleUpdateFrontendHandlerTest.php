<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserRoleUpdateFrontendHandler;

class AccountUserRoleUpdateFrontendHandlerTest extends AbstractAccountUserRoleUpdateHandlerTestCase
{
    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    protected function setUp()
    {
        parent::setUp();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param AccountUserRole $role
     * @param array           $appendUsers
     * @param array           $removeUsers
     * @param array           $assignedUsers
     * @param AccountUser     $accountUser
     * @param array           $expectedUsers
     * @param array           $expectedUsersWithoutRole
     * @dataProvider onSuccessDataProvider
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnSuccess(
        AccountUserRole $role,
        array $appendUsers,
        array $removeUsers,
        array $assignedUsers,
        AccountUser $accountUser,
        array $expectedUsers,
        array $expectedUsersWithoutRole
    ) {
        /** @var AccountUser[] $persistedUsers Array of persisted users */
        $persistedUsers = [];

        $request = new Request();
        $request->setMethod('POST');

        $formMapData = [
            ['appendUsers', $appendUsers],
            ['removeUsers', $removeUsers]
        ];

        foreach ($this->privilegeConfig as $fieldName => $config) {
            $formMapData[] = [$fieldName, []];
        }

        $formMap = [];
        foreach ($formMapData as $formValue) {
            $subForm = $this->getMock('Symfony\Component\Form\FormInterface');
            $subForm->expects($this->once())
                ->method('getData')
                ->willReturn($formValue[1]);

            $formMap[] = [$formValue[0], $subForm];
        }

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('submit')
            ->with($request);

        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $form->expects($this->any())
            ->method('get')
            ->willReturnMap($formMap);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->willReturn($form);

        $handler = new AccountUserRoleUpdateFrontendHandler($this->formFactory, $this->privilegeConfig);

        $this->setRequirementsForHandler($handler);
        $handler->setRequest($request);
        $handler->setSecurityFacade($this->securityFacade);

        $this->doctrineHelper->expects($role->getId() ? $this->once() : $this->never())
            ->method('getEntityRepository')
            ->with($role)
            ->willReturn($this->roleRepository);

        $this->roleRepository->expects($role->getId() ? $this->once() : $this->never())
            ->method('getAssignedUsers')
            ->with($role)
            ->willReturn($assignedUsers);

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($accountUser);

        $manager = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\OroEntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $connection->expects($this->any())
            ->method('beginTransaction');

        $connection->expects($this->any())
            ->method('commit');

        $manager->expects($this->any())
            ->method('persist')
            ->willReturnCallback(
                function ($entity) use (&$persistedUsers) {
                    if ($entity instanceof AccountUser) {
                        $persistedUsers[spl_object_hash($entity)] = $entity;
                    }
                }
            );

        $handler->createForm($role);
        $handledRole = $handler->getHandledRole();

        $this->aclManager->expects($this->exactly(2))
            ->method('getSid')
            ->with($handledRole)
            ->willReturn(new RoleSecurityIdentity($handledRole->getRole()));

        $this->aclManager->expects($this->any())
            ->method('getOid')
            ->willReturn(new ObjectIdentity(1, 2));

        $handler->process($role);


        foreach ($expectedUsers as $expectedUser) {
            $this->assertEquals(
                $handledRole,
                $persistedUsers[spl_object_hash($expectedUser)]->getRole($handledRole->getRole())
            );
        }

        foreach ($expectedUsersWithoutRole as $expectedUserWithoutRole) {
            $this->assertNotEquals(
                $role,
                $persistedUsers[spl_object_hash($expectedUserWithoutRole)]->getRole($role->getRole())
            );
        }
    }

    /**
     * @return array
     */
    public function onSuccessDataProvider()
    {
        $accountUser = $this->createEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', 1);
        $account = $this->createEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 1);
        $accountUser->setAccount($account);

        $roleWithoutAccount = $this->createAccountUserRole('test role1', 1);

        $roleWithAccount = $this->createAccountUserRole('test role3', 3);
        $roleWithAccount->setAccount($account);

        $assignedUsers = $this->createUsersWithRole($roleWithoutAccount, 2, $account);

        $usersForUpdateRole = $this->createUsersWithRole($roleWithAccount, 3, $account);

        return [
            'clone system role (without account) and add all users' => [
                'role'                     => $roleWithoutAccount,
                'appendUsers'              => [],
                'removeUsers'              => [],
                'assignedUsers'            => $assignedUsers,
                'accountUser'              => $accountUser,
                'expectedUsers'            => $assignedUsers,
                'expectedUsersWithoutRole' => $assignedUsers,
            ],
            'clone system role (without account) and add one user'  => [
                'role'                     => $roleWithoutAccount,
                'appendUsers'              => [],
                'removeUsers'              => [$assignedUsers[1]],
                'assignedUsers'            => $assignedUsers,
                'accountUser'              => $accountUser,
                'expectedUsers'            => [$assignedUsers[0]],
                'expectedUsersWithoutRole' => [$assignedUsers[1], $assignedUsers[0]],
            ],
            'change customizable role (with account)'               => [
                'role'                     => $roleWithAccount,
                'appendUsers'              => [$usersForUpdateRole[2]],
                'removeUsers'              => [$usersForUpdateRole[0]],
                'assignedUsers'            => [$usersForUpdateRole[0], $usersForUpdateRole[1]],
                'accountUser'              => $accountUser,
                'expectedUsers'            => [$usersForUpdateRole[2]],
                'expectedUsersWithoutRole' => [$usersForUpdateRole[0]],
            ],
            'create role'                                           => [
                'role'                     => $roleWithAccount,
                'appendUsers'              => [$usersForUpdateRole[2]],
                'removeUsers'              => [$usersForUpdateRole[0]],
                'assignedUsers'            => [$usersForUpdateRole[0], $usersForUpdateRole[1]],
                'accountUser'              => $accountUser,
                'expectedUsers'            => [$usersForUpdateRole[2]],
                'expectedUsersWithoutRole' => [$usersForUpdateRole[0]],
            ],
        ];
    }

    public function testRollBackWhenErrorHappened()
    {
        $request = new Request();
        $request->setMethod('POST');

        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->any())
            ->method('getData')
            ->willReturn([]);

        $form->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['appendUsers', $form],
                    ['removeUsers', $form],
                ]
            );

        $form->expects($this->once())
            ->method('submit')
            ->with($request);

        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->willReturn($form);

        $accountUser = new AccountUser();
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($accountUser);

        $handler = new AccountUserRoleUpdateFrontendHandler($this->formFactory, $this->privilegeConfig);

        $this->setRequirementsForHandler($handler);
        $handler->setRequest($request);
        $handler->setSecurityFacade($this->securityFacade);

        $manager = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\OroEntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);

        $connection->expects($this->any())
            ->method('beginTransaction');

        $connection->expects($this->any())
            ->method('commit')
            ->willThrowException(new \Exception('test message'));

        $connection->expects($this->once())
            ->method('rollBack');

        $this->roleRepository->expects($this->once())
            ->method('getAssignedUsers')
            ->willReturn([]);

        $role = $this->createAccountUserRole('test role', 1);
        $this->setExpectedException('Exception', 'test message');

        $handler->createForm($role);
        $handler->process($role);
    }

    /**
     * @param string   $class
     * @param int|null $id
     *
     * @return object
     */
    protected function createEntity($class, $id = null)
    {
        $entity = new $class();
        if ($id) {
            $reflection = new \ReflectionProperty($class, 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($entity, $id);
        }

        return $entity;
    }
}
