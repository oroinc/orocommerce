<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Handler;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserRoleUpdateFrontendHandler;

class AccountUserRoleUpdateFrontendHandlerTest extends AbstractAccountUserRoleUpdateHandlerTestCase
{
    /** @var  SecurityFacade| \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    protected function setUp()
    {
        parent::setUp();

        $this->handler = new AccountUserRoleUpdateFrontendHandler($this->formFactory, $this->privilegeConfig);
        $this->setRequirementsForHandler($this->handler);

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler->setSecurityFacade($this->securityFacade);
    }

    /**
     * @param AccountUserRole $role
     * @param AccountUserRole $newRole
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
        AccountUserRole $newRole,
        array $appendUsers,
        array $removeUsers,
        array $assignedUsers,
        AccountUser $accountUser,
        array $expectedUsers,
        array $expectedUsersWithoutRole
    ) {
        // Array of persisted users
        /** @var AccountUser[] $persistedUsers */
        $persistedUsers = [];

        $request = new Request();
        $request->setMethod('POST');

        $appendForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $appendForm->expects($this->once())
            ->method('getData')
            ->willReturn($appendUsers);

        $removeForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $removeForm->expects($this->once())
            ->method('getData')
            ->willReturn($removeUsers);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('submit')
            ->with($request);

        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $form->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['appendUsers', $appendForm],
                    ['removeUsers', $removeForm],
                ]
            );

        $this->formFactory->expects($this->once())
            ->method('create')
            ->willReturn($form);

        /** @var \PHPUnit_Framework_MockObject_MockObject|AccountUserRoleUpdateFrontendHandler $handler */
        $handler = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserRoleUpdateFrontendHandler')
            ->setMethods(['processPrivileges'])
            ->setConstructorArgs([$this->formFactory, $this->privilegeConfig])
            ->getMock();

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

        $handler->createForm($newRole);
        $handler->process($role);

        foreach ($expectedUsers as $expectedUser) {
            $this->assertEquals(
                $persistedUsers[spl_object_hash($expectedUser)]->getRole($newRole->getRole()),
                $newRole
            );
        }

        foreach ($expectedUsersWithoutRole as $expectedUserWithoutRole) {
            $this->assertNotEquals(
                $persistedUsers[spl_object_hash($expectedUserWithoutRole)]->getRole($role->getRole()),
                $role
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

        $role = $this->createAccountUserRole('test role1', 1);

        $roleWithAccount = $this->createAccountUserRole('test role3', 3);
        $roleWithAccount->setAccount($account);

        $assignedUsers = $this->createUsersWithRole($role, 2, $account);

        $usersForUpdateRole = $this->createUsersWithRole($roleWithAccount, 3, $account);

        $newRole = $this->createAccountUserRole('new role', 4);

        return [
            'clone system role and add all users' => [
                'role'                     => $role,
                'newRole'                  => clone $role,
                'appendUsers'              => [],
                'removeUsers'              => [],
                'assignedUsers'            => $assignedUsers,
                'accountUser'              => $accountUser,
                'expectedUsers'            => $assignedUsers,
                'expectedUsersWithoutRole' => $assignedUsers,
            ],
            'clone system role and add one user'  => [
                'role'                     => $role,
                'newRole'                  => clone $role,
                'appendUsers'              => [],
                'removeUsers'              => [$assignedUsers[1]],
                'assignedUsers'            => $assignedUsers,
                'accountUser'              => $accountUser,
                'expectedUsers'            => [$assignedUsers[0]],
                'expectedUsersWithoutRole' => [$assignedUsers[1], $assignedUsers[0]],
            ],
            'change customizable role'            => [
                'role'                     => $roleWithAccount,
                'newRole'                  => $roleWithAccount,
                'appendUsers'              => [$usersForUpdateRole[2]],
                'removeUsers'              => [$usersForUpdateRole[0]],
                'assignedUsers'            => [$usersForUpdateRole[0], $usersForUpdateRole[1]],
                'accountUser'              => $accountUser,
                'expectedUsers'            => [$usersForUpdateRole[2]],
                'expectedUsersWithoutRole' => [$usersForUpdateRole[0]],
            ],
            'create role'            => [
                'role'                     => $roleWithAccount,
                'newRole'                  => $roleWithAccount,
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

        /** @var AccountUserRoleUpdateFrontendHandler| /PHPUnit_Framework_MockObject_MockObject $handler */
        $handler = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserRoleUpdateFrontendHandler')
            ->setMethods(['processPrivileges'])
            ->setConstructorArgs([$this->formFactory, $this->privilegeConfig])
            ->getMock();

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

        $handler->createForm(clone $role);
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
