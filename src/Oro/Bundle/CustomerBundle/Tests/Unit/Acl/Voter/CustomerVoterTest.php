<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\CustomerBundle\Acl\Voter\CustomerVoter;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CustomerVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CustomerVoter
     */
    protected $voter;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var CustomerUserProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityProvider;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var AuthenticationTrustResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $trustResolver;

    /**
     * @var CustomerUserRelationsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $relationsProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityProvider = $this->getMockBuilder(CustomerUserProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->trustResolver = $this->createMock(AuthenticationTrustResolverInterface::class);

        $this->relationsProvider = $this->getMockBuilder(CustomerUserRelationsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $services = [
            'oro_customer.security.customer_user_provider' => $this->securityProvider,
            'oro_security.security_facade' => $this->securityFacade,
            'oro_customer.provider.customer_user_relations_provider' => $this->relationsProvider,
        ];

        /* @var $container ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($id) use ($services) {
                return $services[$id];
            });

        $this->voter = new CustomerVoter($this->doctrineHelper, $this->trustResolver);
        $this->voter->setContainer($container);
    }

    /**
     * @param string $class
     * @param bool $supports
     *
     * @dataProvider supportsClassProvider
     */
    public function testSupportsClass($class, $supports)
    {
        $this->assertEquals($supports, $this->voter->supportsClass($class));
    }

    /**
     * @param string $attribute
     * @param bool $expected
     *
     * @dataProvider supportsAttributeProvider
     */
    public function testSupportsAttribute($attribute, $expected)
    {
        $this->assertEquals($expected, $this->voter->supportsAttribute($attribute));
    }

    public function testNotManageableEntityException()
    {
        $object = new \stdClass();
        $class = get_class($object);

        /* @var $token TokenInterface|\PHPUnit_Framework_MockObject_MockObject */
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($this->getCustomerUser(1));

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->will($this->throwException(new NotManageableEntityException($class)));

        $this->assertEquals(
            CustomerVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, [])
        );
    }

    /**
     * @param array $inputData
     * @param int $expectedResult
     *
     * @dataProvider voteProvider
     */
    public function testVote(array $inputData, $expectedResult)
    {
        $object = $inputData['object'];
        if (is_null($object) && is_array($inputData['initObjectParams'])) {
            $object = call_user_func_array([$this, 'getObject'], $inputData['initObjectParams']);
        }
        $class  = is_object($object) ? get_class($object) : null;

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->with($object)
            ->willReturn($class);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn($inputData['objectId']);

        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->with($inputData['isGrantedAttr'], $inputData['isGrantedDescr'])
            ->willReturn($inputData['isGranted']);

        $this->securityProvider->expects($this->any())
            ->method('isGrantedViewBasic')
            ->with($class)
            ->willReturn($inputData['grantedViewBasic']);

        $this->securityProvider->expects($this->any())
            ->method('isGrantedViewLocal')
            ->with($class)
            ->willReturn($inputData['grantedViewLocal']);

        $this->securityProvider->expects($this->any())
            ->method('isGrantedEditBasic')
            ->with($class)
            ->willReturn($inputData['grantedEditBasic']);

        $this->securityProvider->expects($this->any())
            ->method('isGrantedEditLocal')
            ->with($class)
            ->willReturn($inputData['grantedEditLocal']);

        /* @var $token TokenInterface|\PHPUnit_Framework_MockObject_MockObject */
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($inputData['user']);

        $this->trustResolver->expects($this->any())
            ->method('isAnonymous')
            ->with($token)
            ->willReturn(false);

        $this->assertEquals(
            $expectedResult,
            $this->voter->vote($token, $object, $inputData['attributes'])
        );
    }

    /**
     * @return array
     */
    public function supportsClassProvider()
    {
        return [
            'supported class'  => [
                $this->createMock('Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface'),
                true,
            ],
            'not supported class'  => [
                'stdClass',
                false,
            ],
        ];
    }

    /**
     * @return array
     */
    public function supportsAttributeProvider()
    {
        return [
            'ACCOUNT_VIEW'  => ['ACCOUNT_VIEW', true],
            'ACCOUNT_EDIT'  => ['ACCOUNT_EDIT', true],
            'VIEW'          => ['VIEW', false],
            'CREATE'        => ['CREATE', false],
            'EDIT'          => ['EDIT', false],
            'DELETE'        => ['DELETE', false],
            'ASSIGN'        => ['ASSIGN', false]
        ];
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function voteProvider()
    {
        return [
            '!CustomerUser' => [
                'input' => [
                    'objectId'      => 2,
                    'object'        => null,
                    'initObjectParams'  => ['id' => 2],
                    'user'          => new \stdClass(),
                    'attributes'    => [],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => null,
                    'grantedEditLocal' => null,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => CustomerVoter::ACCESS_ABSTAIN,
            ],
            '!Entity' => [
                'input' => [
                    'objectId'      => null,
                    'object'        => null,
                    'initObjectParams'  => null,
                    'user'          => $this->getCustomerUser(1),
                    'attributes'    => [],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => null,
                    'grantedEditLocal' => null,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => CustomerVoter::ACCESS_ABSTAIN,
            ],
            'Entity is !object' => [
                'input' => [
                    'objectId'      => null,
                    'object'        => 'string',
                    'initObjectParams'  => null,
                    'user'          => $this->getCustomerUser(1),
                    'attributes'    => [],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => null,
                    'grantedEditLocal' => null,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => CustomerVoter::ACCESS_ABSTAIN,
            ],
            'Entity::VIEW_BASIC and different users' => [
                'input' => [
                    'objectId'      => 1,
                    'object'        => null,
                    'initObjectParams'  => ['id' => 1, 'customerUserId' => 1],
                    'user'          => $this->getCustomerUser(2),
                    'attributes'    => ['ACCOUNT_VIEW'],
                    'grantedViewBasic' => true,
                    'grantedViewLocal' => false,
                    'grantedEditBasic' => null,
                    'grantedEditLocal' => null,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => CustomerVoter::ACCESS_DENIED,
            ],
            'Entity::VIEW_BASIC and equal users' => [
                'input' => [
                    'objectId'      => 2,
                    'object'        => null,
                    'initObjectParams'  => ['id' => 2, 'customerUserId' => 3],
                    'user'          => $this->getCustomerUser(3),
                    'attributes'    => ['ACCOUNT_VIEW'],
                    'grantedViewBasic' => true,
                    'grantedViewLocal' => false,
                    'grantedEditBasic' => null,
                    'grantedEditLocal' => null,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => CustomerVoter::ACCESS_GRANTED,
            ],
            'Entity::VIEW_LOCAL, different customers and different users' => [
                'input' => [
                    'objectId'      => 4,
                    'object'        => null,
                    'initObjectParams'  => ['id' => 4, 'customerUserId' => 5, 'customerId' => 6],
                    'user'          => $this->getCustomerUser(7, 8),
                    'attributes'    => ['ACCOUNT_VIEW'],
                    'grantedViewBasic' => false,
                    'grantedViewLocal' => true,
                    'grantedEditBasic' => null,
                    'grantedEditLocal' => null,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => CustomerVoter::ACCESS_DENIED,
            ],
            'Entity::VIEW_LOCAL, equal customers and different users' => [
                'input' => [
                    'objectId'      => 9,
                    'object'        => null,
                    'initObjectParams'  => ['id' => 9, 'customerUserId' => 10, 'customerId' => 11],
                    'user'          => $this->getCustomerUser(12, 11),
                    'attributes'    => ['ACCOUNT_VIEW'],
                    'grantedViewBasic' => false,
                    'grantedViewLocal' => true,
                    'grantedEditBasic' => null,
                    'grantedEditLocal' => null,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => CustomerVoter::ACCESS_GRANTED,
            ],
            'Entity::VIEW_LOCAL, different customers and equal users' => [
                'input' => [
                    'objectId'      => 13,
                    'object'        => null,
                    'initObjectParams'  => ['id' => 13, 'customerUserId' => 14, 'customerId' => 15],
                    'user'          => $this->getCustomerUser(14, 17),
                    'attributes'    => ['ACCOUNT_VIEW'],
                    'grantedViewBasic' => false,
                    'grantedViewLocal' => true,
                    'grantedEditBasic' => null,
                    'grantedEditLocal' => null,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => CustomerVoter::ACCESS_GRANTED,
            ],
            'Entity::EDIT_BASIC and different users' => [
                'input' => [
                    'objectId'      => 21,
                    'object'        => null,
                    'initObjectParams'  => ['id' => 21, 'customerUserId' => 21],
                    'user'          => $this->getCustomerUser(22),
                    'attributes'    => ['ACCOUNT_EDIT'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => true,
                    'grantedEditLocal' => false,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => CustomerVoter::ACCESS_DENIED,
            ],
            'Entity::EDIT_BASIC and equal users' => [
                'input' => [
                    'objectId'      => 22,
                    'object'        => null,
                    'initObjectParams'  => ['id' => 22, 'customerUserId' => 23],
                    'user'          => $this->getCustomerUser(23),
                    'attributes'    => ['ACCOUNT_EDIT'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => true,
                    'grantedEditLocal' => false,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => CustomerVoter::ACCESS_GRANTED,
            ],
            'Entity::EDIT_LOCAL, different customers and different users' => [
                'input' => [
                    'objectId'      => 24,
                    'object'        => null,
                    'initObjectParams'  => ['id' => 24, 'customerUserId' => 25, 'customerId' => 26],
                    'user'          => $this->getCustomerUser(27, 28),
                    'attributes'    => ['ACCOUNT_EDIT'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => false,
                    'grantedEditLocal' => true,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => CustomerVoter::ACCESS_DENIED,
            ],
            'Entity::EDIT_LOCAL, equal customers and different users' => [
                'input' => [
                    'objectId'      => 29,
                    'object'        => null,
                    'initObjectParams'  => ['id' => 29, 'customerUserId' => 30, 'customerId' => 31],
                    'user'          => $this->getCustomerUser(32, 31),
                    'attributes'    => ['ACCOUNT_EDIT'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => false,
                    'grantedEditLocal' => true,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => CustomerVoter::ACCESS_GRANTED,
            ],
            'Entity::EDIT_LOCAL, different customers and equal users' => [
                'input' => [
                    'objectId'      => 33,
                    'object'        => null,
                    'initObjectParams'  => ['id' => 33, 'customerUserId' => 34, 'customerId' => 35],
                    'user'          => $this->getCustomerUser(34, 37),
                    'attributes'    => ['ACCOUNT_EDIT'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => false,
                    'grantedEditLocal' => true,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => CustomerVoter::ACCESS_GRANTED,
            ],
            '!ident and !Entity:ACCOUNT_VIEW' => [
                'input' => [
                    'objectId'      => null,
                    'object'        => $this->getIdentity(),
                    'initObjectParams'  => null,
                    'user'          => $this->getCustomerUser(38, 39),
                    'attributes'    => ['ACCOUNT_VIEW'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => false,
                    'grantedEditLocal' => true,
                    'isGranted'        => false,
                    'isGrantedAttr'    => BasicPermissionMap::PERMISSION_VIEW,
                    'isGrantedDescr'   => $this->getDescriptor(),
                ],
                'expected' => CustomerVoter::ACCESS_DENIED,
            ],
            '!ident and !Entity:ACCOUNT_EDIT' => [
                'input' => [
                    'objectId'      => null,
                    'object'        => $this->getIdentity(),
                    'initObjectParams'  => null,
                    'user'          => $this->getCustomerUser(40, 41),
                    'attributes'    => ['ACCOUNT_EDIT'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => false,
                    'grantedEditLocal' => true,
                    'isGranted'        => false,
                    'isGrantedAttr'    => BasicPermissionMap::PERMISSION_EDIT,
                    'isGrantedDescr'   => $this->getDescriptor(),
                ],
                'expected' => CustomerVoter::ACCESS_DENIED,
            ],
            '!ident and Entity:ACCOUNT_VIEW' => [
                'input' => [
                    'objectId'      => null,
                    'object'        => $this->getIdentity(),
                    'initObjectParams'  => null,
                    'user'          => $this->getCustomerUser(42, 43),
                    'attributes'    => ['ACCOUNT_VIEW'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => false,
                    'grantedEditLocal' => true,
                    'isGranted'        => true,
                    'isGrantedAttr'    => BasicPermissionMap::PERMISSION_VIEW,
                    'isGrantedDescr'   => $this->getDescriptor(),
                ],
                'expected' => CustomerVoter::ACCESS_GRANTED,
            ],
            '!ident and Entity:ACCOUNT_EDIT' => [
                'input' => [
                    'objectId'      => null,
                    'object'        => $this->getIdentity(),
                    'initObjectParams'  => null,
                    'user'          => $this->getCustomerUser(44, 45),
                    'attributes'    => ['ACCOUNT_EDIT'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => false,
                    'grantedEditLocal' => true,
                    'isGranted'        => true,
                    'isGrantedAttr'    => BasicPermissionMap::PERMISSION_EDIT,
                    'isGrantedDescr'   => $this->getDescriptor(),
                ],
                'expected' => CustomerVoter::ACCESS_GRANTED,
            ],
        ];
    }

    /**
     * @return ObjectIdentity
     */
    protected function getIdentity()
    {
        return new ObjectIdentity('entity', 'commerce@Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface');
    }

    /**
     * @return string
     */
    protected function getDescriptor()
    {
        return sprintf(
            'entity:%s@%s',
            CustomerUser::SECURITY_GROUP,
            'Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface'
        );
    }

    /**
     * @param int $id
     * @param int $customerUserId
     * @param int $customerId
     * @return CustomerOwnerAwareInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getObject($id, $customerUserId = null, $customerId = null)
    {
        /* @var $object CustomerOwnerAwareInterface|\PHPUnit_Framework_MockObject_MockObject */
        $object = $this->getMockEntity('Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface', $id);

        if ($customerUserId) {
            $object->expects($this->any())
                ->method('getCustomerUser')
                ->willReturn($this->getCustomerUser($customerUserId, $customerId));

            if ($customerId) {
                $object->expects($this->any())
                    ->method('getCustomer')
                    ->willReturn($this->getCustomer($customerId));
            }
        }

        return $object;
    }

    /**
     * @param int $id
     * @param int $customerId
     * @return CustomerUser
     */
    protected function getCustomerUser($id, $customerId = null)
    {
        /* @var $user CustomerUser */
        $user = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUser', $id);

        if ($customerId) {
            $user->setCustomer($this->getCustomer($customerId));
        }

        return $user;
    }

    /**
     * @param int $id
     * @return Customer
     */
    protected function getCustomer($id)
    {
        return $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Customer', $id);
    }

    /**
     * @param string $className
     * @param int $id
     * @param string $primaryKey
     * @return object
     */
    protected function getEntity($className, $id, $primaryKey = 'id')
    {
        static $entities = [];

        if (!isset($entities[$className])) {
            $entities[$className] = [];
        }

        if (!isset($entities[$className][$id])) {
            $entities[$className][$id] = new $className;
            $reflectionClass = new \ReflectionClass($className);
            $method = $reflectionClass->getProperty($primaryKey);
            $method->setAccessible(true);
            $method->setValue($entities[$className][$id], $id);
        }

        return $entities[$className][$id];
    }

    /**
     * @param string $className
     * @param int $id
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockEntity($className, $id)
    {
        static $entities = [];

        if (!isset($entities[$className])) {
            $entities[$className] = [];
        }

        if (!isset($entities[$className][$id])) {
            $mock = $this->getMockBuilder($className)
                ->disableOriginalConstructor()
                ->getMock();

            $entities[$className][$id] = $mock;
        }

        return $entities[$className][$id];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage ContainerInterface not injected
     */
    public function testWithoutContainer()
    {
        $voter = new CustomerVoter($this->doctrineHelper, $this->trustResolver);
        $customerUser = $this->getCustomerUser(1);
        $object = $this->getObject(1);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->with($object)
            ->willReturn(get_class($object));

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        $voter->setClassName(get_class($object));

        /* @var $token TokenInterface|\PHPUnit_Framework_MockObject_MockObject */
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())->method('getUser')->willReturn($customerUser);

        $voter->vote($token, $object, [CustomerVoter::ATTRIBUTE_VIEW]);
    }

    /**
     * @param mixed $object
     *
     * @dataProvider voteAnonymousAbstainProvider
     */
    public function testVoteAnonymousAbstain($object)
    {
        $this->securityFacade->expects($this->never())
            ->method('isGranted');

        /* @var $token TokenInterface|\PHPUnit_Framework_MockObject_MockObject */
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn('anon.');

        $this->trustResolver->expects($this->once())
            ->method('isAnonymous')
            ->with($token)
            ->willReturn(true);

        $this->relationsProvider->expects($this->once())
            ->method('getCustomerIncludingEmpty')
            ->willReturn(new Customer());

        $this->assertEquals(
            CustomerVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, [CustomerVoter::ATTRIBUTE_VIEW])
        );
    }

    /**
     * @return array
     */
    public function voteAnonymousAbstainProvider()
    {
        return [
            '!Entity' => ['object' => null],
            'Entity is !object' => ['object' => 'string']
        ];
    }

    /**
     * @dataProvider voteAnonymousProvider
     *
     * @param string $attribute
     * @param string $permissionAttribute
     * @param bool $isGranted
     * @param int $expectedResult
     */
    public function testVoteAnonymous($attribute, $permissionAttribute, $isGranted, $expectedResult)
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with($permissionAttribute, $this->getDescriptor())
            ->willReturn($isGranted);

        /* @var $token TokenInterface|\PHPUnit_Framework_MockObject_MockObject */
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn('anon.');

        $this->trustResolver->expects($this->once())
            ->method('isAnonymous')
            ->with($token)
            ->willReturn(true);

        $this->relationsProvider->expects($this->once())
            ->method('getCustomerIncludingEmpty')
            ->willReturn(new Customer());

        $this->assertEquals(
            $expectedResult,
            $this->voter->vote($token, $this->getIdentity(), [$attribute])
        );
    }

    /**
     * @return array
     */
    public function voteAnonymousProvider()
    {
        return [
            'view allowed' => [
                CustomerVoter::ATTRIBUTE_VIEW,
                BasicPermissionMap::PERMISSION_VIEW,
                true,
                CustomerVoter::ACCESS_GRANTED
            ],
            'view denied' => [
                CustomerVoter::ATTRIBUTE_VIEW,
                BasicPermissionMap::PERMISSION_VIEW,
                false,
                CustomerVoter::ACCESS_DENIED
            ],
            'edit allowed' => [
                CustomerVoter::ATTRIBUTE_EDIT,
                BasicPermissionMap::PERMISSION_EDIT,
                true,
                CustomerVoter::ACCESS_GRANTED
            ],
            'edit denied' => [
                CustomerVoter::ATTRIBUTE_EDIT,
                BasicPermissionMap::PERMISSION_EDIT,
                false,
                CustomerVoter::ACCESS_DENIED
            ],
        ];
    }
}
