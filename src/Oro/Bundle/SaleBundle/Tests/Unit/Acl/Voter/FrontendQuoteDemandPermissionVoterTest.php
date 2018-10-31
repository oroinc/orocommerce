<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\SaleBundle\Acl\Voter\FrontendQuoteDemandPermissionVoter;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FrontendQuoteDemandPermissionVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $tokenStorage;

    /** @var AuthorizationCheckerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $authorizationChecker;

    /** @var FrontendQuoteDemandPermissionVoter */
    private $voter;

    protected function setUp()
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->voter = new FrontendQuoteDemandPermissionVoter($this->tokenStorage, $this->authorizationChecker);
    }

    public function testVoteWithUnsupportedAttribute(): void
    {
        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects($this->never())
            ->method('getToken');

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->assertEquals(
            FrontendQuoteDemandPermissionVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $this->getQuoteDemand(new CustomerVisitor(), new Quote()), ['ATTR'])
        );
    }

    public function testVoteWithUnsupportedObject(): void
    {
        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects($this->never())
            ->method('getToken');

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->assertEquals(
            FrontendQuoteDemandPermissionVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, new \stdClass(), [])
        );
    }

    /**
     * @dataProvider voteProvider
     *
     * @param TokenInterface $token
     * @param QuoteDemand $quoteDemand
     * @param bool $isGranted
     * @param int $expected
     */
    public function testVote(TokenInterface $token, QuoteDemand $quoteDemand, bool $isGranted, int $expected): void
    {
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->with('oro_sale_quote_frontend_view', $this->isInstanceOf(Quote::class))
            ->willReturn($isGranted);

        $this->assertEquals($expected, $this->voter->vote($token, $quoteDemand, ['VIEW']));
    }

    /**
     * @return array
     */
    public function voteProvider(): array
    {
        $visitor = new CustomerVisitor();

        return [
            'access granted by permission' => [
                'token' => $this->createMock(TokenInterface::class),
                'quoteDemand' => $this->getQuoteDemand($visitor, new Quote()),
                'isGranted' => true,
                'expected' => FrontendQuoteDemandPermissionVoter::ACCESS_GRANTED
            ],
            'access granted by visitor' => [
                'token' => new AnonymousCustomerUserToken('', [], $visitor),
                'quoteDemand' => $this->getQuoteDemand($visitor, new Quote()),
                'isGranted' => false,
                'expected' => FrontendQuoteDemandPermissionVoter::ACCESS_GRANTED
            ],
            'token without visitor' => [
                'token' => new AnonymousCustomerUserToken('', []),
                'quoteDemand' => $this->getQuoteDemand($visitor, new Quote()),
                'isGranted' => true,
                'expected' => FrontendQuoteDemandPermissionVoter::ACCESS_DENIED
            ],
            'token with different visitor' => [
                'token' => new AnonymousCustomerUserToken('', [], new CustomerVisitor()),
                'quoteDemand' => $this->getQuoteDemand($visitor, new Quote()),
                'isGranted' => true,
                'expected' => FrontendQuoteDemandPermissionVoter::ACCESS_DENIED
            ],
            'quote demand without quote' => [
                'token' => $this->createMock(TokenInterface::class),
                'quoteDemand' => $this->getQuoteDemand($visitor),
                'isGranted' => true,
                'expected' => FrontendQuoteDemandPermissionVoter::ACCESS_DENIED
            ],
            'access not granted by permission' => [
                'token' => $this->createMock(TokenInterface::class),
                'quoteDemand' => $this->getQuoteDemand($visitor, new Quote()),
                'isGranted' => false,
                'expected' => FrontendQuoteDemandPermissionVoter::ACCESS_DENIED
            ],
        ];
    }

    /**
     * @param null|CustomerVisitor $visitor
     * @param null|Quote $quote
     * @return QuoteDemand
     */
    private function getQuoteDemand(?CustomerVisitor $visitor = null, ?Quote $quote = null): QuoteDemand
    {
        $quoteDemand = new QuoteDemand();

        if ($visitor) {
            $quoteDemand->setVisitor($visitor);
        }

        if ($quote) {
            $quoteDemand->setQuote($quote);
        }

        return $quoteDemand;
    }
}
