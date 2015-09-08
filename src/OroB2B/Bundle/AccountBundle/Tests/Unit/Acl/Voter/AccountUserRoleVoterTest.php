<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\AccountBundle\Acl\Voter\AccountUserRoleVoter;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Security\AccountUserRoleProvider;

class AccountUserRoleVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AccountUserRoleVoter
     */
    protected $voter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    protected $container;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->voter = new AccountUserRoleVoter($this->doctrineHelper, $this->container);
    }

    protected function tearDown()
    {
        unset($this->voter, $this->doctrineHelper, $this->container);
    }

    /**
     * @param string $class
     * @param string $actualClass
     * @param bool   $expected
     *
     * @dataProvider supportsClassDataProvider
     */
    public function testSupportsClass($class, $actualClass, $expected)
    {
        $this->voter->setClassName($actualClass);
        $this->assertEquals($expected, $this->voter->supportsClass($class));
    }

    /**
     * @return array
     */
    public function supportsClassDataProvider()
    {
        return [
            'supported class'     => ['stdClass', 'stdClass', true],
            'not supported class' => ['NotSupportedClass', 'stdClass', false],
        ];
    }

    /**
     * @param string $attribute
     * @param bool   $expected
     *
     * @dataProvider supportsAttributeDataProvider
     */
    public function testSupportsAttribute($attribute, $expected)
    {
        $this->assertEquals($expected, $this->voter->supportsAttribute($attribute));
    }

    /**
     * @return array
     */
    public function supportsAttributeDataProvider()
    {
        return [
            'VIEW'                         => ['VIEW', false],
            'CREATE'                       => ['CREATE', false],
            'EDIT'                         => ['EDIT', false],
            'DELETE'                       => [AccountUserRoleVoter::ATTRIBUTE_DELETE, true],
            'FRONTEND ACCOUNT ROLE UPDATE' => [AccountUserRoleVoter::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_UPDATE, true],
            'FRONTEND ACCOUNT ROLE VIEW'   => [AccountUserRoleVoter::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_VIEW, true],
        ];
    }

    /**
     * @param bool $isDefaultWebsiteRole
     * @param bool $hasUsers
     * @param int  $expected
     *
     * @dataProvider attributeDeleteDataProvider
     */
    public function testVoteDelete($isDefaultWebsiteRole, $hasUsers, $expected)
    {
        $object = new AccountUserRole();

        $this->getMocksForVote($object);

        $entityRepository = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepository->expects($this->at(0))
            ->method('isDefaultForWebsite')
            ->will($this->returnValue($isDefaultWebsiteRole));

        $entityRepository->expects($this->at(1))
            ->method('hasAssignedUsers')
            ->will($this->returnValue($hasUsers));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with('OroB2BAccountBundle:AccountUserRole')
            ->will($this->returnValue($entityRepository));

        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $token */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $object, [AccountUserRoleVoter::ATTRIBUTE_DELETE])
        );
    }

    /**
     * @return array
     */
    public function attributeDeleteDataProvider()
    {
        return [
            'common role'          => [
                'isDefaultWebsiteRole' => false,
                'hasUsers'             => false,
                'expected'             => VoterInterface::ACCESS_ABSTAIN,
            ],
            'default website role' => [
                'isDefaultWebsiteRole' => true,
                'hasUsers'             => false,
                'expected'             => VoterInterface::ACCESS_DENIED,
            ],
            'role wit users'       => [
                'isDefaultWebsiteRole' => false,
                'hasUsers'             => true,
                'expected'             => VoterInterface::ACCESS_DENIED,
            ],
        ];
    }

    /**
     * @param AccountUser|null $accountUser
     * @param bool             $isGranted
     * @param int              $accountId
     * @param int              $loggedUserAccountId
     * @param int              $expected
     * @param bool             $failAccountUserRole
     *
     * @dataProvider attributeFrontendUpdateViewDataProvider
     */
    public function testVoteFrontendUpdate(
        $accountUser,
        $isGranted,
        $accountId,
        $loggedUserAccountId,
        $expected,
        $failAccountUserRole = false
    ) {
        /** @var Account $roleAccount */
        $roleAccount = $this->createEntity('OroB2B\Bundle\AccountBundle\Entity\Account', $accountId);

        /** @var Account $userAccount */
        $userAccount = $this->createEntity('OroB2B\Bundle\AccountBundle\Entity\Account', $loggedUserAccountId);

        if ($failAccountUserRole) {
            $accountUserRole = new \stdClass();
        } else {
            $accountUserRole = new AccountUserRole();
            $accountUserRole->setAccount($roleAccount);
        }

        if ($accountUser) {
            $accountUser->setAccount($userAccount);
        }

        $this->getMocksForVote($accountUserRole);

        if (!$failAccountUserRole) {
            $this->getMockForUpdateAndView($accountUser, $isGranted, 'isGrantedUpdateAccountUserRole');
        }

        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $token */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $accountUserRole, [AccountUserRoleVoter::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_UPDATE])
        );
    }

    /**
     * @param AccountUser|null $accountUser
     * @param bool             $isGranted
     * @param int              $accountId
     * @param int              $loggedUserAccountId
     * @param int              $expected
     * @param bool             $failAccountUserRole
     * @dataProvider attributeFrontendUpdateViewDataProvider
     */
    public function testVoteFrontendView(
        $accountUser,
        $isGranted,
        $accountId,
        $loggedUserAccountId,
        $expected,
        $failAccountUserRole = false
    ) {
        /** @var Account $roleAccount */
        $roleAccount = $this->createEntity('OroB2B\Bundle\AccountBundle\Entity\Account', $accountId);

        /** @var Account $userAccount */
        $userAccount = $this->createEntity('OroB2B\Bundle\AccountBundle\Entity\Account', $loggedUserAccountId);

        if ($failAccountUserRole) {
            $accountUserRole = new \stdClass();
        } else {
            $accountUserRole = new AccountUserRole();
            $accountUserRole->setAccount($roleAccount);
        }

        if ($accountUser) {
            $accountUser->setAccount($userAccount);
        }

        $this->getMocksForVote($accountUserRole);

        if (!$failAccountUserRole) {
            $this->getMockForUpdateAndView($accountUser, $isGranted, 'isGrantedViewAccountUserRole');
        }

        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $token */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $accountUserRole, [AccountUserRoleVoter::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_VIEW])
        );
    }

    /**
     * @return array
     */
    public function attributeFrontendUpdateViewDataProvider()
    {
        $accountUser = new AccountUser();

        return [
            'account with logged user the same'  => [
                'accountUser'         => $accountUser,
                'isGranted'           => true,
                'accountId'           => 1,
                'loggedUserAccountId' => 1,
                'expected'            => VoterInterface::ACCESS_GRANTED,
            ],
            'account with logged user different' => [
                'accountUser'         => $accountUser,
                'isGranted'           => true,
                'accountId'           => 1,
                'loggedUserAccountId' => 2,
                'expected'            => VoterInterface::ACCESS_ABSTAIN,
            ],
            'isGranted false'                    => [
                'accountUser'         => $accountUser,
                'isGranted'           => false,
                'accountId'           => 1,
                'loggedUserAccountId' => 1,
                'expected'            => VoterInterface::ACCESS_ABSTAIN,
            ],
            'without accountUser'                => [
                'accountUser'         => null,
                'isGranted'           => false,
                'accountId'           => 1,
                'loggedUserAccountId' => 1,
                'expected'            => VoterInterface::ACCESS_ABSTAIN,
            ],
            'without accountUserRole'            => [
                'accountUser'         => null,
                'isGranted'           => false,
                'accountId'           => 1,
                'loggedUserAccountId' => 1,
                'expected'            => VoterInterface::ACCESS_ABSTAIN,
                'failAccountUserRole' => true,
            ],
        ];
    }

    /**
     * @param AccountUserRole|\stdClass $accountUserRole
     */
    protected function getMocksForVote($accountUserRole)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->with($accountUserRole)
            ->will($this->returnValue(get_class($accountUserRole)));

        $this->voter->setClassName(get_class($accountUserRole));

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($accountUserRole, false)
            ->will($this->returnValue(1));
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

    /**
     * @param AccountUser|null $accountUser
     * @param bool             $isGranted
     * @param string           $method
     */
    protected function getMockForUpdateAndView($accountUser, $isGranted, $method)
    {
        /** @var AccountUserRoleProvider|\PHPUnit_Framework_MockObject_MockObject $userRoleProvider */
        $userRoleProvider = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Security\AccountUserRoleProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->expects($this->once())
            ->method('get')
            ->with('orob2b_account.security.account_user_role_provider')
            ->willReturn($userRoleProvider);

        $userRoleProvider->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($accountUser);

        $userRoleProvider->expects($accountUser ? $this->once() : $this->never())
            ->method($method)
            ->willReturn($isGranted);
    }
}
