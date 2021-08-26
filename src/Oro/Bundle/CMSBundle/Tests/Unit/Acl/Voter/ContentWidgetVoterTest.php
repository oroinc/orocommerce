<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\CMSBundle\Acl\Voter\ContentWidgetVoter;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\ContentWidgetUsage;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentWidgetUsageRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ContentWidgetVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $token;

    /** @var ContentWidgetUsageRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ContentWidgetVoter */
    private $voter;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->token = $this->createMock(TokenInterface::class);
        $this->repository = $this->createMock(ContentWidgetUsageRepository::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with(ContentWidgetUsage::class)
            ->willReturn($this->repository);

        $this->voter = new ContentWidgetVoter($doctrineHelper);
    }

    public function testVoteWithUnsupportedObject(): void
    {
        $this->repository->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new \stdClass(), [])
        );
    }

    public function testVoteWithUnsupportedAttribute(): void
    {
        $this->repository->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new ContentWidget(), ['ATTR'])
        );
    }

    /**
     * @dataProvider voteProvider
     */
    public function testVote(bool $found, int $expected): void
    {
        $subject = new ContentWidget();

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['contentWidget' => $subject])
            ->willReturn($found);

        $this->assertEquals($expected, $this->voter->vote($this->token, $subject, ['DELETE']));
    }

    public function voteProvider() : array
    {
        return [
            [
                'found' => true,
                'expected' => VoterInterface::ACCESS_DENIED,
            ],
            [
                'found' => false,
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
        ];
    }
}
