<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\SaleBundle\Acl\Voter\FrontendQuoteDemandPermissionVoter;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class FrontendQuoteDemandPermissionVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendHelper;

    /** @var FrontendQuoteDemandPermissionVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->frontendHelper = $this->createMock(FrontendHelper::class);

        $this->voter = new FrontendQuoteDemandPermissionVoter($this->frontendHelper);
    }

    public function testVoteWithUnsupportedAttribute(): void
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);

        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $this->getQuoteDemand(), ['ATTR'])
        );
    }

    public function testVoteWithUnsupportedObject(): void
    {
        $this->frontendHelper->expects($this->never())
            ->method('isFrontendRequest');

        $token = $this->createMock(TokenInterface::class);

        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, new \stdClass(), [])
        );
    }

    public function testVoteWithNotFrontendRequest(): void
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $token = $this->createMock(TokenInterface::class);

        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $this->getQuoteDemand(), ['VIEW'])
        );
    }

    /**
     * @dataProvider voteProvider
     */
    public function testVote(TokenInterface $token, QuoteDemand $quoteDemand, int $expected): void
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->assertEquals($expected, $this->voter->vote($token, $quoteDemand, ['VIEW']));
    }

    public function voteProvider(): array
    {
        return [
            'access granted for visitor' => [
                'token' => new AnonymousCustomerUserToken(
                    '',
                    [],
                    $this->getCustomerVisitor(42)
                ),
                'quoteDemand' => $this->getQuoteDemand(
                    $this->getCustomerVisitor(42),
                    null,
                    new Quote()
                ),
                'expected' => VoterInterface::ACCESS_GRANTED
            ],
            'access granted for customer user' => [
                'token' => $this->getToken(
                    $this->getCustomerUser(42)
                ),
                'quoteDemand' => $this->getQuoteDemand(
                    null,
                    $this->getCustomerUser(42),
                    new Quote()
                ),
                'expected' => VoterInterface::ACCESS_GRANTED
            ],
            'token without visitor' => [
                'token' => new AnonymousCustomerUserToken('', []),
                'quoteDemand' => $this->getQuoteDemand(
                    $this->getCustomerVisitor(42),
                    null,
                    new Quote()
                ),
                'expected' => VoterInterface::ACCESS_DENIED
            ],
            'quote without visitor' => [
                'token' => new AnonymousCustomerUserToken(
                    '',
                    [],
                    $this->getCustomerVisitor(42)
                ),
                'quoteDemand' => $this->getQuoteDemand(
                    null,
                    null,
                    new Quote()
                ),
                'expected' => VoterInterface::ACCESS_DENIED
            ],
            'different visitor ids' => [
                'token' => new AnonymousCustomerUserToken(
                    '',
                    [],
                    $this->getCustomerVisitor(1001)
                ),
                'quoteDemand' => $this->getQuoteDemand(
                    $this->getCustomerVisitor(2002),
                    null,
                    new Quote()
                ),
                'expected' => VoterInterface::ACCESS_DENIED
            ],
            'token without user' => [
                'token' => $this->getToken(),
                'quoteDemand' => $this->getQuoteDemand(
                    null,
                    $this->getCustomerUser(42),
                    new Quote()
                ),
                'expected' => VoterInterface::ACCESS_DENIED
            ],
            'quote without user' => [
                'token' => $this->getToken(
                    $this->getCustomerUser(42)
                ),
                'quoteDemand' => $this->getQuoteDemand(
                    null,
                    null,
                    new Quote()
                ),
                'expected' => VoterInterface::ACCESS_DENIED
            ],
            'different user ids' => [
                'token' => $this->getToken(
                    $this->getCustomerUser(1001)
                ),
                'quoteDemand' => $this->getQuoteDemand(
                    null,
                    $this->getCustomerUser(2002),
                    new Quote()
                ),
                'expected' => VoterInterface::ACCESS_DENIED
            ],
        ];
    }

    private function getCustomerUser(int $id): CustomerUser
    {
        $user = new CustomerUser();
        ReflectionUtil::setId($user, $id);

        return $user;
    }

    private function getCustomerVisitor(int $id): CustomerVisitor
    {
        $visitor = new CustomerVisitor();
        ReflectionUtil::setId($visitor, $id);

        return $visitor;
    }

    private function getQuoteDemand(
        ?CustomerVisitor $visitor = null,
        ?CustomerUser $user = null,
        ?Quote $quote = null
    ): QuoteDemand {
        $quoteDemand = new QuoteDemand();
        if ($visitor) {
            $quoteDemand->setVisitor($visitor);
        }
        if ($user) {
            $quoteDemand->setCustomerUser($user);
        }
        if ($quote) {
            $quoteDemand->setQuote($quote);
        }

        return $quoteDemand;
    }

    private function getToken(?CustomerUser $customerUser = null): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        if ($customerUser) {
            $token->expects($this->once())
                ->method('getUser')
                ->willReturn($customerUser);
        }

        return $token;
    }
}
