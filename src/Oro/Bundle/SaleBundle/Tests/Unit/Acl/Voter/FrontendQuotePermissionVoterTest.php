<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\SaleBundle\Acl\Voter\FrontendQuotePermissionVoter;
use Oro\Bundle\SaleBundle\Tests\Unit\Stub\QuoteStub as Quote;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FrontendQuotePermissionVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $token;

    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendHelper;

    /** @var FrontendQuotePermissionVoter */
    private $voter;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->token = $this->createMock(TokenInterface::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);

        $this->voter = new FrontendQuotePermissionVoter($this->frontendHelper);
    }

    public function testVoteWithUnsupportedObject()
    {
        $this->assertEquals(
            FrontendQuotePermissionVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new \stdClass(), [])
        );
    }

    public function testVoteForBackend()
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->assertEquals(
            FrontendQuotePermissionVoter::ACCESS_ABSTAIN,
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
                'result' => FrontendQuotePermissionVoter::ACCESS_DENIED,
            ],
            [
                'status' => 'draft',
                'result' => FrontendQuotePermissionVoter::ACCESS_DENIED,
            ],
            [
                'status' => 'template',
                'result' => FrontendQuotePermissionVoter::ACCESS_GRANTED,
            ],
            [
                'status' => 'open',
                'result' => FrontendQuotePermissionVoter::ACCESS_GRANTED,
            ],
            [
                'status' => 'sent_to_customer',
                'result' => FrontendQuotePermissionVoter::ACCESS_GRANTED,
            ],
            [
                'status' => 'expired',
                'result' => FrontendQuotePermissionVoter::ACCESS_GRANTED,
            ],
            [
                'status' => 'accepted',
                'result' => FrontendQuotePermissionVoter::ACCESS_GRANTED,
            ],
            [
                'status' => 'declined',
                'result' => FrontendQuotePermissionVoter::ACCESS_GRANTED,
            ],
            [
                'status' => 'deleted',
                'result' => FrontendQuotePermissionVoter::ACCESS_DENIED,
            ],
            [
                'status' => 'cancelled',
                'result' => FrontendQuotePermissionVoter::ACCESS_GRANTED,
            ],
            [
                'status' => 'submitted_for_review',
                'result' => FrontendQuotePermissionVoter::ACCESS_DENIED,
            ],
            [
                'status' => 'under_review',
                'result' => FrontendQuotePermissionVoter::ACCESS_DENIED,
            ],
            [
                'status' => 'reviewed',
                'result' => FrontendQuotePermissionVoter::ACCESS_DENIED,
            ],
            [
                'status' => 'not_approved',
                'result' => FrontendQuotePermissionVoter::ACCESS_DENIED,
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
