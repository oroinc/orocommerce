<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Acl\Voter\LandingPageVoter;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class LandingPageVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var LandingPageVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->voter = new LandingPageVoter($this->doctrineHelper);
        $this->voter->setClassName(Page::class);
    }

    /**
     * @dataProvider attributesDataProvider
     */
    public function testVote(string $attribute, bool $hasConsents, int $expected)
    {
        $object = new Page();
        $landingPage = $this->createMock(Page::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        $repository = $this->createMock(ConsentAcceptanceRepository::class);
        $repository->expects($this->once())
            ->method('hasLandingPageAcceptedConsents')
            ->willReturn($hasConsents);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(Page::class, 1)
            ->willReturn($landingPage);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->willReturn($repository);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $object, [$attribute])
        );
    }

    public function attributesDataProvider(): array
    {
        return [
            ['EDIT', true, VoterInterface::ACCESS_DENIED],
            ['EDIT', false, VoterInterface::ACCESS_ABSTAIN],
            ['DELETE', true, VoterInterface::ACCESS_DENIED],
            ['DELETE', false, VoterInterface::ACCESS_ABSTAIN]
        ];
    }
}
