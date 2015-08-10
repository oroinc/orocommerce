<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Acl\Voter\AccountVoter;

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

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        /* @var $container ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->any())
            ->method('get')
            ->with('oro_security.security_facade')
            ->willReturn($this->securityFacade)
        ;

        $this->voter = new AccountVoter($this->doctrineHelper, $container);
    }

    /**
     * @param string $class
     * @param string $actualClass
     * @param bool $expected
     *
     * @dataProvider supportsClassProvider
     */
    public function testSupportsClass($class, $actualClass, $expected)
    {
        $this->voter->setClassName($actualClass);
        $this->assertEquals($expected, $this->voter->supportsClass($class));
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
            ->willReturn($object->getId());

        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($inputData['user'])
        ;

        $this->securityFacade->expects($this->any())
            ->method('isGrantedClassMask')
            ->will($this->returnCallback(function ($mask) use ($inputData) {
                return $mask === $inputData['grantedMask'];
            }))
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
            'supported class' => ['stdClass', 'stdClass', true],
            'not supported class' => ['NotSupportedClass', 'stdClass', false]
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
            '!AccountUser' => [
                'input' => [
                    'object'        => $this->getQuote(2),
                    'user'          => new \stdClass(),
                    'grantedMask'   => null,
                ],
                'expected' => AccountVoter::ACCESS_ABSTAIN,
            ],
            'Quote::VIEW_BASIC and different users' => [
                'input' => [
                    'object'        => $this->getQuote(1, 1),
                    'user'          => $this->getAccountUser(2),
                    'grantedMask'   => EntityMaskBuilder::MASK_VIEW_BASIC,
                ],
                'expected' => AccountVoter::ACCESS_ABSTAIN,
            ],
            'Quote::VIEW_BASIC and equal users' => [
                'input' => [
                    'object'        => $this->getQuote(2, 3),
                    'user'          => $this->getAccountUser(3),
                    'grantedMask'   => EntityMaskBuilder::MASK_VIEW_BASIC,
                ],
                'expected' => AccountVoter::ACCESS_GRANTED,
            ],
            'Quote::VIEW_LOCAL, different accounts and different users' => [
                'input' => [
                    'object'        => $this->getQuote(4, 5, 6),
                    'user'          => $this->getAccountUser(7, 8),
                    'grantedMask'   => EntityMaskBuilder::MASK_VIEW_LOCAL,
                ],
                'expected' => AccountVoter::ACCESS_ABSTAIN,
            ],
            'Quote::VIEW_LOCAL, equal accounts and different users' => [
                'input' => [
                    'object'        => $this->getQuote(9, 10, 11),
                    'user'          => $this->getAccountUser(12, 11),
                    'grantedMask'   => EntityMaskBuilder::MASK_VIEW_LOCAL,
                ],
                'expected' => AccountVoter::ACCESS_GRANTED,
            ],
            'Quote::VIEW_LOCAL, different accounts and equal users' => [
                'input' => [
                    'object'        => $this->getQuote(13, 14, 15),
                    'user'          => $this->getAccountUser(14, 17),
                    'grantedMask'   => EntityMaskBuilder::MASK_VIEW_LOCAL,
                ],
                'expected' => AccountVoter::ACCESS_GRANTED,
            ],
        ];
    }

    /**
     * @param int $id
     * @param int $accountUserId
     * @param int $accountId
     * @return Quote
     */
    protected function getQuote($id, $accountUserId = null, $accountId = null)
    {
        /* @var $quote Quote */
        $quote = $this->getEntity('OroB2B\Bundle\SaleBundle\Entity\Quote', $id);

        if ($accountUserId) {
            $quote->setAccountUser($this->getAccountUser($accountUserId, $accountId));

            if ($accountId) {
                $quote->setAccount($this->getAccount($accountId));
            }
        }

        return $quote;
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
}
