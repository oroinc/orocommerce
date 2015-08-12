<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

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

        /* @var $container ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->any())
            ->method('get')
            ->with('orob2b_account.security.account_user_provider')
            ->willReturn($this->securityProvider)
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

    /**
     * @param array $inputData
     * @param int $expectedResult
     *
     * @dataProvider voteProvider
     */
    public function testVote(array $inputData, $expectedResult)
    {
        /* @var $object Quote */
        $object = $inputData['object'];
        $class  = get_class($object);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->with($object)
            ->willReturn($class);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn($inputData['objectId']);

        $this->securityProvider->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($inputData['user'])
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

        $this->voter->setClassName($class);

        /* @var $token TokenInterface|\PHPUnit_Framework_MockObject_MockObject */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->assertEquals(
            $expectedResult,
            $this->voter->vote($token, $object, ['ACCOUNT_VIEW'])
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
            'VIEW'          => ['VIEW', false],
            'CREATE'        => ['CREATE', false],
            'EDIT'          => ['EDIT', false],
            'DELETE'        => ['DELETE', false],
            'ASSIGN'        => ['ASSIGN', false]
        ];
    }

    /**
     * @return array
     */
    public function voteProvider()
    {
        return [
            '!Entity' => [
                'input' => [
                    'objectId'      => 1,
                    'object'        => new \stdClass(),
                    'user'          => null,
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                ],
                'expected' => AccountVoter::ACCESS_ABSTAIN,
            ],
            '!AccountUser' => [
                'input' => [
                    'objectId'      => 2,
                    'object'        => $this->getObject(2),
                    'user'          => new \stdClass(),
                    'grantedViewBasic' => null,
                    'grantedViewLocal' => null,
                ],
                'expected' => AccountVoter::ACCESS_ABSTAIN,
            ],
            'Entity::VIEW_BASIC and different users' => [
                'input' => [
                    'objectId'      => 1,
                    'object'        => $this->getObject(1, 1),
                    'user'          => $this->getAccountUser(2),
                    'grantedViewBasic' => true,
                    'grantedViewLocal' => false,
                ],
                'expected' => AccountVoter::ACCESS_ABSTAIN,
            ],
            'Entity::VIEW_BASIC and equal users' => [
                'input' => [
                    'objectId'      => 2,
                    'object'        => $this->getObject(2, 3),
                    'user'          => $this->getAccountUser(3),
                    'grantedViewBasic' => true,
                    'grantedViewLocal' => false,
                ],
                'expected' => AccountVoter::ACCESS_GRANTED,
            ],
            'Entity::VIEW_LOCAL, different accounts and different users' => [
                'input' => [
                    'objectId'      => 4,
                    'object'        => $this->getObject(4, 5, 6),
                    'user'          => $this->getAccountUser(7, 8),
                    'grantedViewBasic' => false,
                    'grantedViewLocal' => true,
                ],
                'expected' => AccountVoter::ACCESS_ABSTAIN,
            ],
            'Entity::VIEW_LOCAL, equal accounts and different users' => [
                'input' => [
                    'objectId'      => 9,
                    'object'        => $this->getObject(9, 10, 11),
                    'user'          => $this->getAccountUser(12, 11),
                    'grantedViewBasic' => false,
                    'grantedViewLocal' => true,
                ],
                'expected' => AccountVoter::ACCESS_GRANTED,
            ],
            'Entity::VIEW_LOCAL, different accounts and equal users' => [
                'input' => [
                    'objectId'      => 13,
                    'object'        => $this->getObject(13, 14, 15),
                    'user'          => $this->getAccountUser(14, 17),
                    'grantedViewBasic' => false,
                    'grantedViewLocal' => true,
                ],
                'expected' => AccountVoter::ACCESS_GRANTED,
            ],
        ];
    }

    /**
     * @param int $id
     * @param int $accountUserId
     * @param int $accountId
     * @return AccountUserOwnerInterface|\PHPUnit_Framework_MockObject_MockObject
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
            $entities[$className][$id] = $this->getMockBuilder($className)
                ->disableOriginalConstructor()
                ->getMock()
            ;

            $entities[$className][$id]->expects($this->any())
                ->method('getId')
                ->willReturn($id)
            ;
        }

        return $entities[$className][$id];
    }
}
