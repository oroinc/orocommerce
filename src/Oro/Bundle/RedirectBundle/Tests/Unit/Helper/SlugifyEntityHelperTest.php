<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Generator\SlugGenerator;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Helper\SlugifyEntityHelper;
use Oro\Bundle\TranslationBundle\Entity\Language;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SlugifyEntityHelperTest extends TestCase
{
    private SlugGenerator|MockObject $slugGenerator;

    private ConfigManager|MockObject $configManager;

    private ManagerRegistry|MockObject $managerRegistry;

    private SlugifyEntityHelper $helper;

    protected function setUp(): void
    {
        $this->slugGenerator = $this->createMock(SlugGenerator::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->helper = new SlugifyEntityHelper($this->slugGenerator, $this->configManager, $this->managerRegistry);
    }


    public function testFillNoSourceField(): void
    {
        $sluggableEntity = $this->createMock(SluggableInterface::class);

        $configProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects(self::once())
            ->method('getProvider')
            ->with('slug')
            ->willReturn($configProvider);
        $config = new Config(new EntityConfigId('slug'), ['source' => null]);
        $configProvider->expects(self::once())
            ->method('getConfig')
            ->with(get_class($sluggableEntity))
            ->willReturn($config);
        $this->managerRegistry->expects(self::never())
            ->method('getManagerForClass');

        $this->helper->fill($sluggableEntity);
    }

    public function testFillNoLocalizedResources(): void
    {
        $sluggableEntity = $this->createMock(SluggableInterface::class);

        $this->assertSourceField($sluggableEntity, null);

        $sluggableEntity->expects(self::never())
            ->method('getSlugPrototypes');

        $this->helper->fill($sluggableEntity);
    }

    public function testFill(): void
    {
        $sluggableEntity = $this->createMock(SluggableInterface::class);

        $localizedValue1 = new LocalizedFallbackValue();
        $localizedValue1->setString('123');
        $localizedValue2 = new LocalizedFallbackValue();
        $localizedValue2->setLocalization($this->getLocalization('en'));
        $localizedValue2->setString('1234');
        $localizedValue3 = new LocalizedFallbackValue();
        $localizedValue3->setLocalization($this->getLocalization('mx'));
        $localizedResources = new ArrayCollection([$localizedValue1, $localizedValue2, $localizedValue3]);
        $localizedSlugs = new ArrayCollection([]);

        $this->assertSourceField($sluggableEntity, $localizedResources);

        $sluggableEntity->expects(self::once())
            ->method('getSlugPrototypes')
            ->willReturn($localizedSlugs);

        $this->slugGenerator->expects(self::exactly(2))
            ->method('slugify')
            ->withConsecutive(['123'], ['1234'])
            ->willReturnOnConsecutiveCalls('123', '1234');

        $this->helper->fill($sluggableEntity);

        /** @var LocalizedFallbackValue $defaultSlug */
        $defaultSlug = $localizedSlugs->get(0);
        $this->assertNull($defaultSlug->getLocalization());
        $this->assertEquals('123', $defaultSlug->getString());

        /** @var LocalizedFallbackValue $enSlug */
        $enSlug = $localizedSlugs->get(1);
        $this->assertEquals('en', $enSlug->getLocalization()->getLanguage()->getCode());
        $this->assertEquals('1234', $enSlug->getString());
    }

    public function testFillOverwriteSlugLocalizedValue(): void
    {
        $sluggableEntity = $this->createMock(SluggableInterface::class);

        $localizedValueRes = new LocalizedFallbackValue();
        $localizedValueRes->setLocalization($this->getLocalization('en', 'English'));
        $localizedValueRes->setString('12345');
        $localizedValueSlug = new LocalizedFallbackValue();
        $localizedValueSlug->setLocalization($this->getLocalization('en', 'English'));
        $localizedResources = new ArrayCollection([$localizedValueRes]);
        $localizedSlugs = new ArrayCollection([$localizedValueSlug]);

        $this->assertSourceField($sluggableEntity, $localizedResources);

        $sluggableEntity->expects(self::once())
            ->method('getSlugPrototypes')
            ->willReturn($localizedSlugs);

        $this->slugGenerator->expects(self::once())
            ->method('slugify')
            ->with('12345')
            ->willReturn('12345');

        $this->helper->fill($sluggableEntity);

        /** @var LocalizedFallbackValue $enSlug */
        $enSlug = $localizedSlugs->get(0);
        $this->assertEquals('en', $enSlug->getLocalization()->getLanguage()->getCode());
        $this->assertEquals('12345', $enSlug->getString());
    }

    private function getLocalization(string $code, string $name = ""): Localization
    {
        $language = new Language();
        $language->setCode($code);

        $localization = new Localization();
        $localization->setLanguage($language);
        $localization->setName($name);

        return $localization;
    }

    protected function assertSourceField(
        MockObject|SluggableInterface $sluggableEntity,
        ?ArrayCollection $localizedResources
    ): void {
        $configProvider = $this->createMock(ConfigProvider::class);
        $objectManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->reflFields['sourceField'] = $this->createMock(\ReflectionProperty::class);

        $this->configManager->expects(self::once())
            ->method('getProvider')
            ->with('slug')
            ->willReturn($configProvider);
        $config = new Config(new EntityConfigId('slug'), ['source' => 'sourceField']);
        $configProvider->expects(self::once())
            ->method('getConfig')
            ->with(get_class($sluggableEntity))
            ->willReturn($config);
        $this->managerRegistry->expects(self::once())
            ->method('getManagerForClass')
            ->with(get_class($sluggableEntity))
            ->willReturn($objectManager);
        $objectManager->expects(self::once())
            ->method('getClassMetadata')
            ->with(get_class($sluggableEntity))
            ->willReturn($classMetadata);
        $classMetadata->reflFields['sourceField']->expects(self::once())
            ->method('getValue')
            ->with($sluggableEntity)
            ->willReturn($localizedResources);
    }
}
