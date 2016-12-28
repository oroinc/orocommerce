<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\CheckoutBundle\Acl\Voter\CheckoutVoter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CheckoutVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutVoter
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
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()
            ->getMock();

        $services = [
            'oro_security.security_facade' => $this->securityFacade,
        ];

        /* @var $container ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
        $container = $this->getMock(ContainerInterface::class);
        $container->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($id) use ($services) {
                return $services[$id];
            });

        $this->voter = new CheckoutVoter($this->doctrineHelper);
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
     * @return array
     */
    public function supportsClassProvider()
    {
        return [
            'supported class'  => [
                $this->getMock(CheckoutSourceEntityInterface::class),
                true,
            ],
            'not supported class'  => [
                'stdClass',
                false,
            ],
        ];
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
     * @return array
     */
    public function supportsAttributeProvider()
    {
        return [
            'CHECKOUT_CREATE'  => ['CHECKOUT_CREATE', true],
            'VIEW'          => ['VIEW', false],
            'CREATE'        => ['CREATE', false],
            'EDIT'          => ['EDIT', false],
            'DELETE'        => ['DELETE', false],
            'ASSIGN'        => ['ASSIGN', false]
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage ContainerInterface not injected
     */
    public function testWithoutContainer()
    {
        $voter = new CheckoutVoter($this->doctrineHelper);
        $object = $this->getObject(1);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        $voter->setClassName(get_class($object));

        /* @var $token TokenInterface|\PHPUnit_Framework_MockObject_MockObject */
        $token = $this->getMock(TokenInterface::class);

        $voter->vote($token, $object, [CheckoutVoter::ATTRIBUTE_CREATE]);
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
        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(function ($attribute, $object) use ($inputData) {
                if ($attribute == $inputData['isGrantedAttr']) {
                    return $inputData['isGranted'];
                }
                if ($attribute == $inputData['isGrantedAttrCheckout']) {
                    return $inputData['isGrantedCheckout'];
                }
            });

        /* @var $token TokenInterface|\PHPUnit_Framework_MockObject_MockObject */
        $token = $this->getMock(TokenInterface::class);

        $this->assertEquals(
            $expectedResult,
            $this->voter->vote($token, $object, $inputData['attributes'])
        );
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function voteProvider()
    {
        return [
            '!Entity' => [
                'input' => [
                    'object' => null,
                    'attributes' => [],
                    'isGranted' => null,
                    'isGrantedAttr' => null,
                    'isGrantedCheckout' => null,
                    'isGrantedAttrCheckout' => null,
                ],
                'expected' => CheckoutVoter::ACCESS_ABSTAIN,
            ],
            'Entity is !object' => [
                'input' => [
                    'object' => 'string',
                    'attributes' => [],
                    'isGranted' => null,
                    'isGrantedAttr' => null,
                    'isGrantedCheckout' => null,
                    'isGrantedAttrCheckout' => null,
                ],
                'expected' => CheckoutVoter::ACCESS_ABSTAIN,
            ],
            'isGranted on CREATE and !VIEW' => [
                'input' => [
                    'object' => $this->getIdentity(),
                    'attributes' => ['CHECKOUT_CREATE'],
                    'isGranted' => false,
                    'isGrantedAttr' => BasicPermissionMap::PERMISSION_VIEW,
                    'isGrantedCheckout' => true,
                    'isGrantedAttrCheckout' => BasicPermissionMap::PERMISSION_CREATE,
                ],
                'expected' => CheckoutVoter::ACCESS_DENIED,
            ],
            'isGranted on CREATE and VIEW' => [
                'input' => [
                    'object' => $this->getIdentity(),
                    'attributes' => ['CHECKOUT_CREATE'],
                    'isGranted' => true,
                    'isGrantedAttr' => BasicPermissionMap::PERMISSION_VIEW,
                    'isGrantedCheckout' => true,
                    'isGrantedAttrCheckout' => BasicPermissionMap::PERMISSION_CREATE,
                ],
                'expected' => CheckoutVoter::ACCESS_GRANTED,
            ],
        ];
    }

    /**
     * @return ObjectIdentity
     */
    protected function getIdentity()
    {
        return new ObjectIdentity('entity', 'commerce@Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface');
    }

    /**
     * @param int $id
     * @return CheckoutSourceEntityInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getObject($id)
    {
        /* @var $object CheckoutSourceEntityInterface|\PHPUnit_Framework_MockObject_MockObject */
        $object = $this->getMockEntity(CheckoutSourceEntityInterface::class, $id);

        return $object;
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
}
