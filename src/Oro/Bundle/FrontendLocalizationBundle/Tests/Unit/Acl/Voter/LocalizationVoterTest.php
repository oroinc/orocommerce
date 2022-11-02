<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigValueRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendLocalizationBundle\Acl\Voter\LocalizationVoter;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class LocalizationVoterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var LocalizationRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var LocalizationVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ConfigValueRepository::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(function ($object) {
                return method_exists($object, 'getId') ? $object->getId() : null;
            });

        $this->voter = new LocalizationVoter($this->doctrineHelper);
        $this->voter->setClassName(Localization::class);
    }

    /**
     * @dataProvider voteDataProvider
     */
    public function testVote(bool $isUsed, bool $isCached, object $object, string $attribute, int $expected)
    {
        $currentId = $object->getId();
        $notCurrentId = $currentId + 1;
        $configValue1 = $this->getEntity(ConfigValue::class, ['id' => 1, 'textValue' => $currentId]);
        $configValue2 = $this->getEntity(ConfigValue::class, ['id' => 2, 'textValue' => $notCurrentId]);

        $r = new \ReflectionClass(LocalizationVoter::class);
        $p = $r->getProperty('usedLocalizationIds');
        $p->setAccessible(true);
        $p->setValue($isCached ? [$currentId, $notCurrentId] : null);
        $this->doctrineHelper->expects($this->exactly((int)!$isCached))
            ->method('getEntityRepositoryForClass')
            ->with(ConfigValue::class)
            ->willReturn($this->repository);

        $this->repository->expects($this->exactly((int)!$isCached))
            ->method('findBy')
            ->willReturn($isUsed ? [$configValue1, $configValue2] : [$configValue2]);

        $this->assertSame(
            $expected,
            $this->voter->vote($this->createMock(TokenInterface::class), $object, [$attribute])
        );
    }

    public function voteDataProvider(): array
    {
        $localization = $this->getEntity(Localization::class, ['id' => 42]);

        return [
            'abstain when not supported attribute' => [
                'isUsed' => true,
                'isCached' => true,
                'object' => $localization,
                'attribute' => 'TEST',
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'abstain when not supported class' => [
                'isUsed' => true,
                'isCached' => true,
                'object' => $this->getEntity(Item::class, ['id' => 42]),
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'abstain when new entity' => [
                'isUsed' => true,
                'isCached' => true,
                'object' => $this->getEntity(Localization::class),
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'denied when used' => [
                'isUsed' => true,
                'isCached' => false,
                'object' => $localization,
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_DENIED,
            ],
            'abstain when not used' => [
                'isUsed' => false,
                'isCached' => false,
                'object' => $localization,
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'cached' => [
                'isUsed' => true,
                'isCached' => true,
                'object' => $localization,
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_DENIED,
            ]
        ];
    }
}
