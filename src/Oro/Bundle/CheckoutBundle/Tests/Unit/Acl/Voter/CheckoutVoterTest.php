<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\CheckoutBundle\Acl\Voter\CheckoutVoter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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
     * @var AuthorizationCheckerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $authorizationChecker;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $services = [
            'security.authorization_checker' => $this->authorizationChecker,
        ];

        /* @var $container ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($id) use ($services) {
                return $services[$id];
            });

        $this->voter = new CheckoutVoter($this->doctrineHelper);
        $this->voter->setContainer($container);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage ContainerInterface not injected
     */
    public function testWithoutContainer()
    {
        $object = $this->createMock(CheckoutSourceEntityInterface::class);

        /* @var $token TokenInterface|\PHPUnit_Framework_MockObject_MockObject */
        $token = $this->createMock(TokenInterface::class);

        $voter = new CheckoutVoter($this->doctrineHelper);
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
        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(function ($attribute) use ($inputData) {
                if ($attribute === $inputData['isGrantedAttr']) {
                    return $inputData['isGranted'];
                }
                if ($attribute === $inputData['isGrantedAttrCheckout']) {
                    return $inputData['isGrantedCheckout'];
                }
                return null;
            });

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->with($inputData['object'])
            ->willReturn($inputData['object']);

        /* @var $token TokenInterface|\PHPUnit_Framework_MockObject_MockObject */
        $token = $this->createMock(TokenInterface::class);

        $this->assertEquals(
            $expectedResult,
            $this->voter->vote($token, $inputData['object'], $inputData['attributes'])
        );
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function voteProvider()
    {
        $object = $this->createMock(CheckoutSourceEntityInterface::class);

        $permissionCreate = 'CREATE;entity:OroCheckoutBundle:Checkout';

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
                    'isGrantedAttrCheckout' => $permissionCreate,
                ],
                'expected' => CheckoutVoter::ACCESS_DENIED,
            ],
            'isGranted on VIEW and !CREATE' => [
                'input' => [
                    'object' => $this->getIdentity(),
                    'attributes' => ['CHECKOUT_CREATE'],
                    'isGranted' => true,
                    'isGrantedAttr' => BasicPermissionMap::PERMISSION_VIEW,
                    'isGrantedCheckout' => false,
                    'isGrantedAttrCheckout' => $permissionCreate,
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
                    'isGrantedAttrCheckout' => $permissionCreate,
                ],
                'expected' => CheckoutVoter::ACCESS_GRANTED,
            ],
            'isGranted on CREATE and VIEW for Entity instance' => [
                'input' => [
                    'object' => $object,
                    'attributes' => ['CHECKOUT_CREATE'],
                    'isGranted' => true,
                    'isGrantedAttr' => BasicPermissionMap::PERMISSION_VIEW,
                    'isGrantedCheckout' => true,
                    'isGrantedAttrCheckout' => $permissionCreate,
                ],
                'expected' => CheckoutVoter::ACCESS_GRANTED,
            ],
            'ATTRIBUTE_CREATE not in attributes' => [
                'input' => [
                    'object' => $object,
                    'attributes' => []
                ],
                'expected' => CheckoutVoter::ACCESS_ABSTAIN
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
}
