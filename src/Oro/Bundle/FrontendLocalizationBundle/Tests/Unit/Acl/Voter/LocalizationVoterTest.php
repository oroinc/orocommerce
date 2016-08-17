<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigValueRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendLocalizationBundle\Acl\Voter\LocalizationVoter;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;

use Oro\Component\Testing\Unit\EntityTrait;

class LocalizationVoterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const ENTITY_CLASS = Localization::class;

    /** @var LocalizationRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var LocalizationVoter */
    protected $voter;

    public function setUp()
    {
        $this->repository = $this->getMockBuilder(ConfigValueRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->willReturnCallback(
                function ($object) {
                    return get_class($object);
                }
            );
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(
                function ($object) {
                    return method_exists($object, 'getId') ? $object->getId() : null;
                }
            );

        $this->voter = new LocalizationVoter($this->doctrineHelper);
        $this->voter->setClassName(self::ENTITY_CLASS);
    }

    /**
     * @dataProvider voteDataProvider
     *
     * @param bool $isUsed
     * @param bool $isCached
     * @param object $object
     * @param string $attribute
     * @param int $expected
     */
    public function testVote($isUsed, $isCached, $object, $attribute, $expected)
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

        $this->assertEquals($expected, $this->voter->vote($this->getToken(), $object, [$attribute]));
    }

    /**
     * @return array
     */
    public function voteDataProvider()
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
                'object' => $this->getEntity('Oro\Bundle\TestFrameworkBundle\Entity\Item', ['id' => 42]),
                'attribute' => 'DELETE',
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'abstain when new entity' => [
                'isUsed' => true,
                'isCached' => true,
                'object' => $this->getEntity('Oro\Bundle\LocaleBundle\Entity\Localization'),
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenInterface
     */
    protected function getToken()
    {
        return $this->getMockBuilder(TokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
