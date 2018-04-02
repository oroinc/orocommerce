<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider as ApplicationProvider;
use Oro\Bundle\SaleBundle\Acl\Voter\FrontendQuotePermissionVoter;
use Oro\Bundle\SaleBundle\Tests\Unit\Stub\QuoteStub as Quote;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FrontendQuotePermissionVoterTest extends \PHPUnit_Framework_TestCase
{
    /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $token;

    /** @var CurrentApplicationProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $applicationProvider;

    /** @var FrontendQuotePermissionVoter */
    protected $voter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->token = $this->createMock(TokenInterface::class);
        $this->applicationProvider = $this->createMock(CurrentApplicationProviderInterface::class);

        $this->voter = new FrontendQuotePermissionVoter($this->applicationProvider);
    }

    public function testVoteWithUnsupportedObject()
    {
        $this->assertEquals(
            FrontendQuotePermissionVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new \stdClass(), [])
        );
    }

    public function testVoteWithUnsupportedApplication()
    {
        $this->applicationProvider->expects($this->once())
            ->method('getCurrentApplication')
            ->willReturn(ApplicationProvider::DEFAULT_APPLICATION);

        $this->assertEquals(
            FrontendQuotePermissionVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new Quote(), ['ATTR'])
        );
    }

    /**
     * @param string $internalStatus
     * @param int $expectedResult
     *
     * @dataProvider voteProvider
     */
    public function testVote(string $internalStatus, int $expectedResult)
    {
        $this->applicationProvider->expects($this->once())
            ->method('getCurrentApplication')
            ->willReturn(ApplicationProvider::COMMERCE_APPLICATION);

        $quote = $this->getQuoteWithInternalStatus($internalStatus);

        $this->assertEquals(
            $expectedResult,
            $this->voter->vote($this->token, $quote, ['ATTR'])
        );
    }

    /**
     * @return array
     */
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

    /**
     * @param string $status
     * @return Quote
     */
    protected function getQuoteWithInternalStatus(string $status) : Quote
    {
        $quote = new Quote();
        $quote->setInternalStatus(new StubEnumValue($status, $status));

        return $quote;
    }
}
