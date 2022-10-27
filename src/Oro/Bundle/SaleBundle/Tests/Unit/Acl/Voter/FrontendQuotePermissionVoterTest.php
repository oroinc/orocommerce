<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\SaleBundle\Acl\Voter\FrontendQuotePermissionVoter;
use Oro\Bundle\SaleBundle\Tests\Unit\Stub\QuoteStub as Quote;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class FrontendQuotePermissionVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $token;

    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendHelper;

    /** @var FrontendQuotePermissionVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->token = $this->createMock(TokenInterface::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);

        $this->voter = new FrontendQuotePermissionVoter($this->frontendHelper);
    }

    public function testVoteWithUnsupportedObject()
    {
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new \stdClass(), [])
        );
    }

    public function testVoteForBackend()
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new Quote(), ['ATTR'])
        );
    }

    /**
     * @dataProvider voteProvider
     */
    public function testVote(string $internalStatus, int $expectedResult)
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $quote = $this->getQuoteWithInternalStatus($internalStatus);

        $this->assertEquals(
            $expectedResult,
            $this->voter->vote($this->token, $quote, ['ATTR'])
        );
    }

    public function voteProvider() : array
    {
        return [
            [
                'status' => 'unknown',
                'result' => VoterInterface::ACCESS_DENIED,
            ],
            [
                'status' => 'draft',
                'result' => VoterInterface::ACCESS_DENIED,
            ],
            [
                'status' => 'template',
                'result' => VoterInterface::ACCESS_GRANTED,
            ],
            [
                'status' => 'open',
                'result' => VoterInterface::ACCESS_GRANTED,
            ],
            [
                'status' => 'sent_to_customer',
                'result' => VoterInterface::ACCESS_GRANTED,
            ],
            [
                'status' => 'expired',
                'result' => VoterInterface::ACCESS_GRANTED,
            ],
            [
                'status' => 'accepted',
                'result' => VoterInterface::ACCESS_GRANTED,
            ],
            [
                'status' => 'declined',
                'result' => VoterInterface::ACCESS_GRANTED,
            ],
            [
                'status' => 'deleted',
                'result' => VoterInterface::ACCESS_DENIED,
            ],
            [
                'status' => 'cancelled',
                'result' => VoterInterface::ACCESS_GRANTED,
            ],
            [
                'status' => 'submitted_for_review',
                'result' => VoterInterface::ACCESS_DENIED,
            ],
            [
                'status' => 'under_review',
                'result' => VoterInterface::ACCESS_DENIED,
            ],
            [
                'status' => 'reviewed',
                'result' => VoterInterface::ACCESS_DENIED,
            ],
            [
                'status' => 'not_approved',
                'result' => VoterInterface::ACCESS_DENIED,
            ],
        ];
    }

    private function getQuoteWithInternalStatus(string $status): Quote
    {
        $quote = new Quote();
        $quote->setInternalStatus(new TestEnumValue($status, $status));

        return $quote;
    }
}
