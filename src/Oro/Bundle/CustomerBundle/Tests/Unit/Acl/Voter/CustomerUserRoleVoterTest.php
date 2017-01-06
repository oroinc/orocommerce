<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CustomerBundle\Acl\Voter\CustomerUserRoleVoter;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CustomerUserRoleVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CustomerUserRoleVoter
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

        $this->container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->voter = new CustomerUserRoleVoter($this->doctrineHelper);
        $this->voter->setContainer($this->container);
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
            'DELETE'                       => [CustomerUserRoleVoter::ATTRIBUTE_DELETE, true],
            'FRONTEND ACCOUNT ROLE UPDATE' => [CustomerUserRoleVoter::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_UPDATE, true],
            'FRONTEND ACCOUNT ROLE VIEW'   => [CustomerUserRoleVoter::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_VIEW, true],
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
        $object = new CustomerUserRole();

        $this->getMocksForVote($object);

        $entityRepository = $this
            ->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserRoleRepository')
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
            ->with('OroCustomerBundle:CustomerUserRole')
            ->will($this->returnValue($entityRepository));

        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $token */
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $object, [CustomerUserRoleVoter::ATTRIBUTE_DELETE])
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
                'expected'             => VoterInterface::ACCESS_GRANTED,
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
     * @param bool             $failCustomerUserRole
     *
     * @dataProvider attributeFrontendUpdateViewDataProvider
     */
    public function testVoteFrontendUpdate(
        $accountUser,
        $isGranted,
        $accountId,
        $loggedUserAccountId,
        $expected,
        $failCustomerUserRole = false
    ) {
        /** @var Account $roleAccount */
        $roleAccount = $this->createEntity('Oro\Bundle\CustomerBundle\Entity\Account', $accountId);

        /** @var Account $userAccount */
        $userAccount = $this->createEntity('Oro\Bundle\CustomerBundle\Entity\Account', $loggedUserAccountId);

        if ($failCustomerUserRole) {
            $customerUserRole = new \stdClass();
        } else {
            $customerUserRole = new CustomerUserRole();
            $customerUserRole->setAccount($roleAccount);
        }

        if ($accountUser) {
            $accountUser->setAccount($userAccount);
        }

        $this->getMocksForVote($customerUserRole);

        if (!$failCustomerUserRole) {
            $this->getMockForUpdateAndView($accountUser, $customerUserRole, $isGranted, 'EDIT');
        }

        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $token */
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->assertEquals(
            $expected,
            $this->voter
                ->vote($token, $customerUserRole, [CustomerUserRoleVoter::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_UPDATE])
        );
    }

    /**
     * @param AccountUser|null $accountUser
     * @param bool             $isGranted
     * @param int              $accountId
     * @param int              $loggedUserAccountId
     * @param int              $expected
     * @param bool             $failCustomerUserRole
     * @dataProvider attributeFrontendUpdateViewDataProvider
     */
    public function testVoteFrontendView(
        $accountUser,
        $isGranted,
        $accountId,
        $loggedUserAccountId,
        $expected,
        $failCustomerUserRole = false
    ) {
        /** @var Account $roleAccount */
        $roleAccount = $this->createEntity('Oro\Bundle\CustomerBundle\Entity\Account', $accountId);

        /** @var Account $userAccount */
        $userAccount = $this->createEntity('Oro\Bundle\CustomerBundle\Entity\Account', $loggedUserAccountId);

        if ($failCustomerUserRole) {
            $customerUserRole = new \stdClass();
        } else {
            $customerUserRole = new CustomerUserRole();
            $customerUserRole->setAccount($roleAccount);
        }

        if ($accountUser) {
            $accountUser->setAccount($userAccount);
        }

        $this->getMocksForVote($customerUserRole);

        if (!$failCustomerUserRole) {
            $this->getMockForUpdateAndView($accountUser, $customerUserRole, $isGranted, 'VIEW');
        }

        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $token */
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $customerUserRole, [CustomerUserRoleVoter::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_VIEW])
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
            'isGranted false'                    => [
                'accountUser'         => $accountUser,
                'isGranted'           => false,
                'accountId'           => 1,
                'loggedUserAccountId' => 1,
                'expected'            => VoterInterface::ACCESS_DENIED,
            ],
            'without accountUser'                => [
                'accountUser'         => null,
                'isGranted'           => false,
                'accountId'           => 1,
                'loggedUserAccountId' => 1,
                'expected'            => VoterInterface::ACCESS_DENIED,
            ],
            'without customerUserRole'            => [
                'accountUser'         => null,
                'isGranted'           => false,
                'accountId'           => 1,
                'loggedUserAccountId' => 1,
                'expected'            => VoterInterface::ACCESS_ABSTAIN,
                'failCustomerUserRole' => true,
            ],
        ];
    }

    /**
     * @param CustomerUserRole|\stdClass $customerUserRole
     */
    protected function getMocksForVote($customerUserRole)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->with($customerUserRole)
            ->will($this->returnValue(get_class($customerUserRole)));

        $this->voter->setClassName(get_class($customerUserRole));

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($customerUserRole, false)
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
     * @param CustomerUserRole $customerUserRole
     * @param bool             $isGranted
     * @param string           $attribute
     */
    protected function getMockForUpdateAndView($accountUser, $customerUserRole, $isGranted, $attribute)
    {
        /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject $securityFacade */
        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->with('oro_security.security_facade')
            ->willReturn($securityFacade);

        $securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($accountUser);

        $securityFacade->expects($accountUser ? $this->once() : $this->never())
            ->method('isGranted')
            ->with($attribute, $customerUserRole)
            ->willReturn($isGranted);
    }
}
