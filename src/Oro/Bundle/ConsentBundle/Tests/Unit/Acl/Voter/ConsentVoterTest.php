<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Acl\Voter;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConsentBundle\Acl\Voter\ConsentVoter;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ConsentVoterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ConsentVoter */
    protected $voter;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityRepository */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->repository = $this->createMock(ConsentAcceptanceRepository::class);

        $this->voter = new ConsentVoter($this->doctrineHelper);
    }

    /**
     * @param string $attribute
     * @param int $expected
     * @dataProvider voteForNotExistingConsentDataProvider
     */
    public function testVoteForNotExistingConsent($attribute, $expected)
    {
        $this->voter->setClassName(Consent::class);

        /** @var Consent $consent */
        $consent = $this->getEntity(Consent::class, ['id' => 32]);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($consent)
            ->willReturn(Consent::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($consent, false)
            ->willReturn($consent->getId());

        $this->doctrineHelper
            ->expects($this->once())
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

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $consent, [$attribute])
        );
    }

    /**
     * @return array
     */
    public function voteForNotExistingConsentDataProvider()
    {
        return [
            'EDIT' => ['EDIT', ConsentVoter::ACCESS_ABSTAIN],
            'DELETE' => ['DELETE', ConsentVoter::ACCESS_ABSTAIN]
        ];
    }

    /**
     * @param string $attribute
     * @param Consent $consent
     * @param bool $hasConsentAcceptances
     * @param int $expected
     * @dataProvider attributesDataProvider
     */
    public function testVote($attribute, $consent, $hasConsentAcceptances, $expected)
    {
        $this->voter->setClassName(Consent::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($consent)
            ->willReturn(Consent::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($consent, false)
            ->willReturn($consent->getId());

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(ConsentAcceptance::class)
            ->willReturn($this->repository);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->with(Consent::class, $consent->getId())
            ->willReturn($consent);

        $this->repository->expects($this->once())
            ->method('hasConsentAcceptancesByConsent')
            ->with($consent)
            ->willReturn($hasConsentAcceptances);

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $consent, [$attribute])
        );
    }

    /**
     * @return array
     */
    public function attributesDataProvider()
    {
        /** @var Consent $consent */
        $consent = $this->getEntity(Consent::class, ['id' => 32]);

        return [
            'edit without accepted consents' => [
                'attribute' => 'EDIT',
                'consent' => $consent,
                'hasConsentAcceptances' => false,
                'expected' => ConsentVoter::ACCESS_ABSTAIN,
            ],
            'edit with accepted consents' => [
                'attribute' => 'EDIT',
                'consent' => $consent,
                'hasConsentAcceptances' => true,
                'expected' => ConsentVoter::ACCESS_DENIED,
            ],
            'delete without accepted consents' => [
                'attribute' => 'DELETE',
                'consent' => $consent,
                'hasConsentAcceptances' => false,
                'expected' => ConsentVoter::ACCESS_ABSTAIN,
            ],
            'delete with accepted consents' => [
                'attribute' => 'DELETE',
                'consent' => $consent,
                'hasConsentAcceptances' => true,
                'expected' => ConsentVoter::ACCESS_DENIED,
            ]
        ];
    }
}
