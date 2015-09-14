<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Acl\Voter\AccountVoter;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Security\AccountUserProvider;

class AccountVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AccountVoter
     */
    protected $voter;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var AccountUserProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityProvider;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityProvider = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Security\AccountUserProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $services = [
            'orob2b_account.security.account_user_provider' => $this->securityProvider,
            'oro_security.security_facade' => $this->securityFacade,
        ];

        /* @var $container ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($id) use ($services) {
                return $services[$id];
            })
        ;

        $this->voter = new AccountVoter($this->doctrineHelper, $container);
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
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($this->getAccountUser(1))
        ;

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->will($this->throwException(new NotManageableEntityException($class)));

        $this->assertEquals(
            AccountVoter::ACCESS_ABSTAIN,
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
            ->willReturn($inputData['isGranted'])
        ;

        $this->securityProvider->expects($this->any())
            ->method('isGrantedViewBasic')
            ->with($class)
            ->willReturn($inputData['grantedViewBasic'])
        ;

        $this->securityProvider->expects($this->any())
            ->method('isGrantedViewLocal')
            ->with($class)
            ->willReturn($inputData['grantedViewLocal'])
        ;

        $this->securityProvider->expects($this->any())
            ->method('isGrantedEditBasic')
            ->with($class)
            ->willReturn($inputData['grantedEditBasic'])
        ;

        $this->securityProvider->expects($this->any())
            ->method('isGrantedEditLocal')
            ->with($class)
            ->willReturn($inputData['grantedEditLocal'])
        ;

        $this->voter->setClassName($class);

        /* @var $token TokenInterface|\PHPUnit_Framework_MockObject_MockObject */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($inputData['user'])
        ;

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
                $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface'),
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
            '!AccountUser' => [
                'input' => [
                    'objectId'      => 2,
                    'object'        => $this->getObject(2),
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
                'expected' => AccountVoter::ACCESS_ABSTAIN,
            ],
            '!Entity' => [
                'input' => [
                    'objectId'      => null,
                    'object'        => null,
                    'user'          => $this->getAccountUser(1),
                    'attributes'    => [],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => null,
                    'grantedEditLocal' => null,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => AccountVoter::ACCESS_ABSTAIN,
            ],
            'Entity is !object' => [
                'input' => [
                    'objectId'      => null,
                    'object'        => 'string',
                    'user'          => $this->getAccountUser(1),
                    'attributes'    => [],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => null,
                    'grantedEditLocal' => null,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => AccountVoter::ACCESS_ABSTAIN,
            ],
            'Entity::VIEW_BASIC and different users' => [
                'input' => [
                    'objectId'      => 1,
                    'object'        => $this->getObject(1, 1),
                    'user'          => $this->getAccountUser(2),
                    'attributes'    => ['ACCOUNT_VIEW'],
                    'grantedViewBasic' => true,
                    'grantedViewLocal' => false,
                    'grantedEditBasic' => null,
                    'grantedEditLocal' => null,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => AccountVoter::ACCESS_ABSTAIN,
            ],
            'Entity::VIEW_BASIC and equal users' => [
                'input' => [
                    'objectId'      => 2,
                    'object'        => $this->getObject(2, 3),
                    'user'          => $this->getAccountUser(3),
                    'attributes'    => ['ACCOUNT_VIEW'],
                    'grantedViewBasic' => true,
                    'grantedViewLocal' => false,
                    'grantedEditBasic' => null,
                    'grantedEditLocal' => null,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => AccountVoter::ACCESS_GRANTED,
            ],
            'Entity::VIEW_LOCAL, different accounts and different users' => [
                'input' => [
                    'objectId'      => 4,
                    'object'        => $this->getObject(4, 5, 6),
                    'user'          => $this->getAccountUser(7, 8),
                    'attributes'    => ['ACCOUNT_VIEW'],
                    'grantedViewBasic' => false,
                    'grantedViewLocal' => true,
                    'grantedEditBasic' => null,
                    'grantedEditLocal' => null,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => AccountVoter::ACCESS_ABSTAIN,
            ],
            'Entity::VIEW_LOCAL, equal accounts and different users' => [
                'input' => [
                    'objectId'      => 9,
                    'object'        => $this->getObject(9, 10, 11),
                    'user'          => $this->getAccountUser(12, 11),
                    'attributes'    => ['ACCOUNT_VIEW'],
                    'grantedViewBasic' => false,
                    'grantedViewLocal' => true,
                    'grantedEditBasic' => null,
                    'grantedEditLocal' => null,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => AccountVoter::ACCESS_GRANTED,
            ],
            'Entity::VIEW_LOCAL, different accounts and equal users' => [
                'input' => [
                    'objectId'      => 13,
                    'object'        => $this->getObject(13, 14, 15),
                    'user'          => $this->getAccountUser(14, 17),
                    'attributes'    => ['ACCOUNT_VIEW'],
                    'grantedViewBasic' => false,
                    'grantedViewLocal' => true,
                    'grantedEditBasic' => null,
                    'grantedEditLocal' => null,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => AccountVoter::ACCESS_GRANTED,
            ],
            'Entity::EDIT_BASIC and different users' => [
                'input' => [
                    'objectId'      => 21,
                    'object'        => $this->getObject(21, 21),
                    'user'          => $this->getAccountUser(22),
                    'attributes'    => ['ACCOUNT_EDIT'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => true,
                    'grantedEditLocal' => false,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => AccountVoter::ACCESS_ABSTAIN,
            ],
            'Entity::EDIT_BASIC and equal users' => [
                'input' => [
                    'objectId'      => 22,
                    'object'        => $this->getObject(22, 23),
                    'user'          => $this->getAccountUser(23),
                    'attributes'    => ['ACCOUNT_EDIT'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => true,
                    'grantedEditLocal' => false,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => AccountVoter::ACCESS_GRANTED,
            ],
            'Entity::EDIT_LOCAL, different accounts and different users' => [
                'input' => [
                    'objectId'      => 24,
                    'object'        => $this->getObject(24, 25, 26),
                    'user'          => $this->getAccountUser(27, 28),
                    'attributes'    => ['ACCOUNT_EDIT'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => false,
                    'grantedEditLocal' => true,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => AccountVoter::ACCESS_ABSTAIN,
            ],
            'Entity::EDIT_LOCAL, equal accounts and different users' => [
                'input' => [
                    'objectId'      => 29,
                    'object'        => $this->getObject(29, 30, 31),
                    'user'          => $this->getAccountUser(32, 31),
                    'attributes'    => ['ACCOUNT_EDIT'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => false,
                    'grantedEditLocal' => true,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => AccountVoter::ACCESS_GRANTED,
            ],
            'Entity::EDIT_LOCAL, different accounts and equal users' => [
                'input' => [
                    'objectId'      => 33,
                    'object'        => $this->getObject(33, 34, 35),
                    'user'          => $this->getAccountUser(34, 37),
                    'attributes'    => ['ACCOUNT_EDIT'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => false,
                    'grantedEditLocal' => true,
                    'isGranted'        => null,
                    'isGrantedAttr'    => null,
                    'isGrantedDescr'   => null,
                ],
                'expected' => AccountVoter::ACCESS_GRANTED,
            ],
            '!ident and !Entity:ACCOUNT_VIEW' => [
                'input' => [
                    'objectId'      => null,
                    'object'        => $this->getIdent(),
                    'user'          => $this->getAccountUser(38, 39),
                    'attributes'    => ['ACCOUNT_VIEW'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => false,
                    'grantedEditLocal' => true,
                    'isGranted'        => false,
                    'isGrantedAttr'    => BasicPermissionMap::PERMISSION_VIEW,
                    'isGrantedDescr'   => $this->getDescriptor(),
                ],
                'expected' => AccountVoter::ACCESS_ABSTAIN,
            ],
            '!ident and !Entity:ACCOUNT_EDIT' => [
                'input' => [
                    'objectId'      => null,
                    'object'        => $this->getIdent(),
                    'user'          => $this->getAccountUser(40, 41),
                    'attributes'    => ['ACCOUNT_EDIT'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => false,
                    'grantedEditLocal' => true,
                    'isGranted'        => false,
                    'isGrantedAttr'    => BasicPermissionMap::PERMISSION_EDIT,
                    'isGrantedDescr'   => $this->getDescriptor(),
                ],
                'expected' => AccountVoter::ACCESS_ABSTAIN,
            ],
            '!ident and Entity:ACCOUNT_VIEW' => [
                'input' => [
                    'objectId'      => null,
                    'object'        => $this->getIdent(),
                    'user'          => $this->getAccountUser(42, 43),
                    'attributes'    => ['ACCOUNT_VIEW'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => false,
                    'grantedEditLocal' => true,
                    'isGranted'        => true,
                    'isGrantedAttr'    => BasicPermissionMap::PERMISSION_VIEW,
                    'isGrantedDescr'   => $this->getDescriptor(),
                ],
                'expected' => AccountVoter::ACCESS_GRANTED,
            ],
            '!ident and Entity:ACCOUNT_EDIT' => [
                'input' => [
                    'objectId'      => null,
                    'object'        => $this->getIdent(),
                    'user'          => $this->getAccountUser(44, 45),
                    'attributes'    => ['ACCOUNT_EDIT'],
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                    'grantedEditBasic' => false,
                    'grantedEditLocal' => true,
                    'isGranted'        => true,
                    'isGrantedAttr'    => BasicPermissionMap::PERMISSION_EDIT,
                    'isGrantedDescr'   => $this->getDescriptor(),
                ],
                'expected' => AccountVoter::ACCESS_GRANTED,
            ],
        ];
    }

    /**
     * @return ObjectIdentity
     */
    protected function getIdent()
    {
        return new ObjectIdentity('entity', 'OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface');
    }

    /**
     * @return string
     */
    protected function getDescriptor()
    {
        return sprintf(
            'entity:%s@%s',
            AccountUser::SECURITY_GROUP,
            'OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface'
        );
    }

    /**
     * @param int $id
     * @param int $accountUserId
     * @param int $accountId
     * @return AccountOwnerAwareInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getObject($id, $accountUserId = null, $accountId = null)
    {
        /* @var $object AccountOwnerAwareInterface|\PHPUnit_Framework_MockObject_MockObject */
        $object = $this->getMockEntity('OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface', $id);

        if ($accountUserId) {
            $object->expects($this->any())
                ->method('getAccountUser')
                ->willReturn($this->getAccountUser($accountUserId, $accountId))
            ;

            if ($accountId) {
                $object->expects($this->any())
                    ->method('getAccount')
                    ->willReturn($this->getAccount($accountId))
                ;
            }
        }

        return $object;
    }

    /**
     * @param int $id
     * @param int $accountId
     * @return AccountUser
     */
    protected function getAccountUser($id, $accountId = null)
    {
        /* @var $user AccountUser */
        $user = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', $id);

        if ($accountId) {
            $user->setAccount($this->getAccount($accountId));
        }

        return $user;
    }

    /**
     * @param int $id
     * @return Account
     */
    protected function getAccount($id)
    {
        return $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', $id);
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
                ->getMock()
            ;

            $mock->expects($this->any())
                ->method('getId')
                ->willReturn($id)
            ;

            $entities[$className][$id] = $mock;
        }

        return $entities[$className][$id];
    }
}
