<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\CheckoutBundle\Acl\Voter\CheckoutVoter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class CheckoutVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var CheckoutVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->voter = new CheckoutVoter($this->authorizationChecker);
    }

    private function getIdentity(): ObjectIdentity
    {
        return new ObjectIdentity('entity', 'commerce@' . CheckoutSourceEntityInterface::class);
    }

    /**
     * @dataProvider voteProvider
     */
    public function testVote(array $inputData, int $expectedResult)
    {
        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(function ($attribute, $object) use ($inputData) {
                if ($attribute === $inputData['isGrantedAttr']) {
                    self::assertSame($inputData['object'], $object);
                    return $inputData['isGranted'];
                }
                if ($attribute === $inputData['isGrantedAttrCheckout']) {
                    self::assertEquals('entity:' . Checkout::class, $object);
                    return $inputData['isGrantedCheckout'];
                }
                return null;
            });

        $token = $this->createMock(TokenInterface::class);

        $this->assertEquals(
            $expectedResult,
            $this->voter->vote($token, $inputData['object'], $inputData['attributes'])
        );
    }

    public function voteProvider(): array
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
                'expected' => VoterInterface::ACCESS_ABSTAIN,
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
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'isGranted on CREATE and !VIEW' => [
                'input' => [
                    'object' => $this->getIdentity(),
                    'attributes' => ['CHECKOUT_CREATE'],
                    'isGranted' => false,
                    'isGrantedAttr' => 'VIEW',
                    'isGrantedCheckout' => true,
                    'isGrantedAttrCheckout' => 'CREATE',
                ],
                'expected' => VoterInterface::ACCESS_DENIED,
            ],
            'isGranted on VIEW and !CREATE' => [
                'input' => [
                    'object' => $this->getIdentity(),
                    'attributes' => ['CHECKOUT_CREATE'],
                    'isGranted' => true,
                    'isGrantedAttr' => 'VIEW',
                    'isGrantedCheckout' => false,
                    'isGrantedAttrCheckout' => 'CREATE',
                ],
                'expected' => VoterInterface::ACCESS_DENIED,
            ],
            'isGranted on CREATE and VIEW' => [
                'input' => [
                    'object' => $this->getIdentity(),
                    'attributes' => ['CHECKOUT_CREATE'],
                    'isGranted' => true,
                    'isGrantedAttr' => 'VIEW',
                    'isGrantedCheckout' => true,
                    'isGrantedAttrCheckout' => 'CREATE',
                ],
                'expected' => VoterInterface::ACCESS_GRANTED,
            ],
            'isGranted on CREATE and VIEW for Entity instance' => [
                'input' => [
                    'object' => $this->createMock(CheckoutSourceEntityInterface::class),
                    'attributes' => ['CHECKOUT_CREATE'],
                    'isGranted' => true,
                    'isGrantedAttr' => 'VIEW',
                    'isGrantedCheckout' => true,
                    'isGrantedAttrCheckout' => 'CREATE',
                ],
                'expected' => VoterInterface::ACCESS_GRANTED,
            ],
            'ATTRIBUTE_CREATE not in attributes' => [
                'input' => [
                    'object' => $this->createMock(CheckoutSourceEntityInterface::class),
                    'attributes' => []
                ],
                'expected' => VoterInterface::ACCESS_ABSTAIN
            ],
        ];
    }
}
