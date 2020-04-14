<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\CheckoutBundle\Acl\Voter\CheckoutVoter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CheckoutVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var CheckoutVoter */
    private $voter;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->voter = new CheckoutVoter($this->authorizationChecker);
    }

    /**
     * @return ObjectIdentity
     */
    private function getIdentity()
    {
        return new ObjectIdentity('entity', 'commerce@Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface');
    }

    /**
     * @dataProvider voteProvider
     */
    public function testVote(array $inputData, int $expectedResult)
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

        /* @var $token TokenInterface|\PHPUnit\Framework\MockObject\MockObject */
        $token = $this->createMock(TokenInterface::class);

        $this->assertEquals(
            $expectedResult,
            $this->voter->vote($token, $inputData['object'], $inputData['attributes'])
        );
    }

    /**
     * @return array
     */
    public function voteProvider()
    {
        $object = $this->createMock(CheckoutSourceEntityInterface::class);
        $permissionCreate = 'CREATE;entity:' . Checkout::class;

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
                    'isGrantedAttr' => 'VIEW',
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
                    'isGrantedAttr' => 'VIEW',
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
                    'isGrantedAttr' => 'VIEW',
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
                    'isGrantedAttr' => 'VIEW',
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
}
