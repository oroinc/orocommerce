<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\SaleBundle\Acl\Voter\FrontendQuoteDemandPermissionVoter;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FrontendQuoteDemandPermissionVoterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendHelper;

    /** @var FrontendQuoteDemandPermissionVoter */
    private $voter;

    protected function setUp()
    {
        $this->frontendHelper = $this->createMock(FrontendHelper::class);

        $this->voter = new FrontendQuoteDemandPermissionVoter($this->frontendHelper);
    }

    public function testVoteWithUnsupportedAttribute(): void
    {
        $this->frontendHelper->expects($this->never())
            ->method('isFrontendRequest');

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);

        $this->assertEquals(
            FrontendQuoteDemandPermissionVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $this->getQuoteDemand(), ['ATTR'])
        );
    }

    public function testVoteWithUnsupportedObject(): void
    {
        $this->frontendHelper->expects($this->never())
            ->method('isFrontendRequest');

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);

        $this->assertEquals(
            FrontendQuoteDemandPermissionVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, new \stdClass(), [])
        );
    }

    public function testVoteWithNotFrontendRequest(): void
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);

        $this->assertEquals(
            FrontendQuoteDemandPermissionVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $this->getQuoteDemand(), ['VIEW'])
        );
    }

    /**
     * @dataProvider voteProvider
     *
     * @param TokenInterface $token
     * @param QuoteDemand $quoteDemand
     * @param int $expected
     */
    public function testVote(TokenInterface $token, QuoteDemand $quoteDemand, int $expected): void
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->assertEquals($expected, $this->voter->vote($token, $quoteDemand, ['VIEW']));
    }

    /**
     * @return array
     */
    public function voteProvider(): array
    {
        return [
            'access granted for visitor' => [
                'token' => new AnonymousCustomerUserToken(
                    '',
                    [],
                    $this->getEntity(CustomerVisitor::class, ['id' => 42])
                ),
                'quoteDemand' => $this->getQuoteDemand(
                    $this->getEntity(CustomerVisitor::class, ['id' => 42]),
                    null,
                    new Quote()
                ),
                'expected' => FrontendQuoteDemandPermissionVoter::ACCESS_GRANTED
            ],
            'access granted for customer user' => [
                'token' => $this->getToken(
                    $this->getEntity(CustomerUser::class, ['id' => 42])
                ),
                'quoteDemand' => $this->getQuoteDemand(
                    null,
                    $this->getEntity(CustomerUser::class, ['id' => 42]),
                    new Quote()
                ),
                'expected' => FrontendQuoteDemandPermissionVoter::ACCESS_GRANTED
            ],
            'token without visitor' => [
                'token' => new AnonymousCustomerUserToken('', []),
                'quoteDemand' => $this->getQuoteDemand(
                    $this->getEntity(CustomerVisitor::class, ['id' => 42]),
                    null,
                    new Quote()
                ),
                'expected' => FrontendQuoteDemandPermissionVoter::ACCESS_DENIED
            ],
            'quote without visitor' => [
                'token' => new AnonymousCustomerUserToken(
                    '',
                    [],
                    $this->getEntity(CustomerVisitor::class, ['id' => 42])
                ),
                'quoteDemand' => $this->getQuoteDemand(
                    null,
                    null,
                    new Quote()
                ),
                'expected' => FrontendQuoteDemandPermissionVoter::ACCESS_DENIED
            ],
            'different visitor ids' => [
                'token' => new AnonymousCustomerUserToken(
                    '',
                    [],
                    $this->getEntity(CustomerVisitor::class, ['id' => 1001])
                ),
                'quoteDemand' => $this->getQuoteDemand(
                    $this->getEntity(CustomerVisitor::class, ['id' => 2002]),
                    null,
                    new Quote()
                ),
                'expected' => FrontendQuoteDemandPermissionVoter::ACCESS_DENIED
            ],
            'token without user' => [
                'token' => $this->getToken(),
                'quoteDemand' => $this->getQuoteDemand(
                    null,
                    $this->getEntity(CustomerUser::class, ['id' => 42]),
                    new Quote()
                ),
                'expected' => FrontendQuoteDemandPermissionVoter::ACCESS_DENIED
            ],
            'quote without user' => [
                'token' => $this->getToken(
                    $this->getEntity(CustomerUser::class, ['id' => 42])
                ),
                'quoteDemand' => $this->getQuoteDemand(
                    null,
                    null,
                    new Quote()
                ),
                'expected' => FrontendQuoteDemandPermissionVoter::ACCESS_DENIED
            ],
            'different user ids' => [
                'token' => $this->getToken(
                    $this->getEntity(CustomerUser::class, ['id' => 1001])
                ),
                'quoteDemand' => $this->getQuoteDemand(
                    null,
                    $this->getEntity(CustomerUser::class, ['id' => 2002]),
                    new Quote()
                ),
                'expected' => FrontendQuoteDemandPermissionVoter::ACCESS_DENIED
            ],
        ];
    }

    /**
     * @param null|CustomerVisitor $visitor
     * @param null|CustomerUser $user
     * @param null|Quote $quote
     * @return QuoteDemand
     */
    private function getQuoteDemand(
        ?CustomerVisitor $visitor = null,
        ?CustomerUser $user = null,
        ?Quote $quote = null
    ): QuoteDemand {
        $args = [];

        if ($visitor) {
            $args['visitor'] = $visitor;
        }

        if ($user) {
            $args['customerUser'] = $user;
        }

        if ($quote) {
            $args['quote'] = $quote;
        }

        /** @var QuoteDemand|\PHPUnit\Framework\MockObject\MockObject $quoteDemand */
        $quoteDemand = $this->getEntity(QuoteDemand::class, $args);

        return $quoteDemand;
    }

    /**
     * @param null|CustomerUser $customerUser
     * @return TokenInterface
     */
    private function getToken(?CustomerUser $customerUser = null): TokenInterface
    {
        /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject $token */
        $token = $this->createMock(TokenInterface::class);

        if ($customerUser) {
            $token
                ->method('getUser')
                ->willReturn($customerUser);
        }

        return $token;
    }
}
