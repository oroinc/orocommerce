<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Acl\Voter;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConsentBundle\Acl\Voter\ConsentVoter;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ConsentVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ConsentVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->repository = $this->createMock(ConsentAcceptanceRepository::class);

        $this->voter = new ConsentVoter($this->doctrineHelper);
        $this->voter->setClassName(Consent::class);
    }

    /**
     * @dataProvider voteForNotExistingConsentDataProvider
     */
    public function testVoteForNotExistingConsent(string $attribute, int $expected)
    {
        $consent = new Consent();
        ReflectionUtil::setId($consent, 32);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($consent, false)
            ->willReturn($consent->getId());

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(Consent::class, $consent->getId())
            ->willReturn($consent);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(ConsentAcceptance::class)
            ->willReturn($this->repository);

        $this->repository->expects($this->any())
            ->method('hasConsentAcceptancesByConsent')
            ->with($consent)
            ->willReturn(false);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $consent, [$attribute])
        );
    }

    public function voteForNotExistingConsentDataProvider(): array
    {
        return [
            'EDIT' => ['EDIT', VoterInterface::ACCESS_ABSTAIN],
            'DELETE' => ['DELETE', VoterInterface::ACCESS_ABSTAIN]
        ];
    }

    /**
     * @dataProvider attributesDataProvider
     */
    public function testVote(string $attribute, Consent $consent, bool $hasConsentAcceptances, int $expected)
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($consent, false)
            ->willReturn($consent->getId());

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(ConsentAcceptance::class)
            ->willReturn($this->repository);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(Consent::class, $consent->getId())
            ->willReturn($consent);

        $this->repository->expects($this->once())
            ->method('hasConsentAcceptancesByConsent')
            ->with($consent)
            ->willReturn($hasConsentAcceptances);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $consent, [$attribute])
        );
    }

    public function attributesDataProvider(): array
    {
        $consent = new Consent();
        ReflectionUtil::setId($consent, 32);

        return [
            'edit without accepted consents' => [
                'attribute' => 'EDIT',
                'consent' => $consent,
                'hasConsentAcceptances' => false,
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'edit with accepted consents' => [
                'attribute' => 'EDIT',
                'consent' => $consent,
                'hasConsentAcceptances' => true,
                'expected' => VoterInterface::ACCESS_DENIED,
            ],
            'delete without accepted consents' => [
                'attribute' => 'DELETE',
                'consent' => $consent,
                'hasConsentAcceptances' => false,
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'delete with accepted consents' => [
                'attribute' => 'DELETE',
                'consent' => $consent,
                'hasConsentAcceptances' => true,
                'expected' => VoterInterface::ACCESS_DENIED,
            ]
        ];
    }
}
