<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Layout\DataProvider\LocalizedLinksDataProvider;
use Oro\Bundle\SEOBundle\Model\DTO\AlternateUrl;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\Constraints\Locale;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LocalizedLinksDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const BASE_SLUG = '/base-slug';
    private const EN_SLUG = '/en-slug';
    private const FR_SLUG = '/fr-slug';

    /** @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var UserLocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userLocalizationManager;

    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var LocalizedLinksDataProvider */
    private $dataProvider;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->userLocalizationManager = $this->createMock(UserLocalizationManager::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->dataProvider = new LocalizedLinksDataProvider(
            $this->urlGenerator,
            $this->configManager,
            $this->userLocalizationManager,
            $this->validator
        );
    }

    public function testGetAlternatesWithOneEnabledLocalizationWithSlugAwareInterface()
    {
        $entity = $this->createMock(SlugAwareInterface::class);

        $this->configureUserLocalizationManagerWithLocalizations([$this->getEntity(Localization::class)]);

        $this->urlGenerator->expects($this->never())
            ->method('getAbsoluteUrl');

        $this->assertEmpty($this->dataProvider->getAlternates($entity));
    }

    public function testGetAlternatesWithOneEnabledLocalizationAndSluggableInterfaceAndDirectUrlSupported()
    {
        $entity = $this->createMock(SluggableInterface::class);

        $this->configureUserLocalizationManagerWithLocalizations([$this->getEntity(Localization::class)]);

        $this->configureConfigManager(Configuration::DIRECT_URL, true);

        $this->urlGenerator->expects($this->never())
            ->method('getAbsoluteUrl');

        $this->assertEmpty($this->dataProvider->getAlternates($entity));
    }

    public function testGetAlternatesWithSlugAwareInterfaceData()
    {
        $languageEn = $this->getEntity(Language::class, ['code' => 'en']);
        $languageFr = $this->getEntity(Language::class, ['code' => 'fr_FR']);
        $enLocalization = $this->getEntity(Localization::class, ['id' => 1, 'language' => $languageEn]);
        $frLocalization = $this->getEntity(Localization::class, ['id' => 2, 'language' => $languageFr]);

        $this->configureUserLocalizationManagerWithLocalizations([$enLocalization, $frLocalization]);

        $entity = $this->configureSlugAwareDataWithSlugs([
            $this->getEntity(Slug::class, ['url' => self::BASE_SLUG]),
            $this->getEntity(Slug::class, ['url' => self::EN_SLUG, 'localization' => $enLocalization]),
            $this->getEntity(Slug::class, ['url' => self::FR_SLUG, 'localization' => $frLocalization])
        ]);

        $this->validator->expects($this->any())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->configureConfigManager(Configuration::DIRECT_URL, true);

        $baseUrl = 'http://domain.com/base_slug';
        $enUrl = 'http://domain.com/en_slug';
        $frUrl = 'http://domain.com/fr_slug';

        $this->urlGenerator->expects($this->exactly(3))
            ->method('getAbsoluteUrl')
            ->withConsecutive([self::BASE_SLUG], [self::EN_SLUG], [self::FR_SLUG])
            ->willReturnOnConsecutiveCalls($baseUrl, $enUrl, $frUrl);

        $expectedData = [
            new AlternateUrl($baseUrl),
            new AlternateUrl($enUrl, $enLocalization),
            new AlternateUrl($frUrl, $frLocalization)
        ];

        $this->assertEquals($expectedData, $this->dataProvider->getAlternates($entity));
    }

    /**
     * @dataProvider directUrlNotSupportedDataProvider
     */
    public function testGetAlternatesWithSluggableInterfaceDataAndDirectUrlNotSupportedAndManyLocalizationsEnabled(
        string $canonicalUrlType,
        bool $enableDirectUrl
    ) {
        $languageEn = $this->getEntity(Language::class, ['code' => 'en']);
        $languageFr = $this->getEntity(Language::class, ['code' => 'fr_FR']);
        $enLocalization = $this->getEntity(Localization::class, ['id' => 1, 'language' => $languageEn]);
        $frLocalization = $this->getEntity(Localization::class, ['id' => 2, 'language' => $languageFr]);

        $this->configureUserLocalizationManagerWithLocalizations([$enLocalization, $frLocalization]);

        $this->configureConfigManager($canonicalUrlType, $enableDirectUrl);

        $this->validator->expects($this->any())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $entity = $this->createMock(SluggableInterface::class);

        $systemUrl = 'http://domain.com/some/entity/3';
        $this->urlGenerator->expects($this->once())
            ->method('getSystemUrl')
            ->with($entity)
            ->willReturn($systemUrl);

        $expectedAlternateUrls = [new AlternateUrl($systemUrl)];

        $this->assertEquals($expectedAlternateUrls, $this->dataProvider->getAlternates($entity));
    }

    /**
     * @dataProvider directUrlNotSupportedDataProvider
     */
    public function testGetAlternatesWithSluggableInterfaceDataAndDirectUrlNotSupportedAndOneLocalizationEnabled(
        string $canonicalUrlType,
        bool $enableDirectUrl
    ) {
        $languageEn = $this->getEntity(Language::class, ['code' => 'en']);
        $enLocalization = $this->getEntity(Localization::class, ['id' => 1, 'language' => $languageEn]);

        $this->configureUserLocalizationManagerWithLocalizations([$enLocalization]);

        $this->configureConfigManager($canonicalUrlType, $enableDirectUrl);

        $this->validator->expects($this->any())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $entity = $this->createMock(SluggableInterface::class);

        $this->assertEmpty($this->dataProvider->getAlternates($entity));
    }

    public function directUrlNotSupportedDataProvider(): array
    {
        return [
            'direct url is not enabled' => [
                'canonicalUrlType' => Configuration::DIRECT_URL,
                'enableDirectUrl' => false
            ],
            'canonical url type is not direct' => [
                'canonicalUrlType' => Configuration::SYSTEM_URL,
                'enableDirectUrl' => true
            ],
            'direct url is not enabled and canonical url type is not direct' => [
                'canonicalUrlType' => Configuration::SYSTEM_URL,
                'enableDirectUrl' => false
            ],
        ];
    }

    public function testGetAlternatesWithSluggableInterfaceDataAndDirectUrlSupported()
    {
        $languageEn = $this->getEntity(Language::class, ['code' => 'en']);
        $languageFr = $this->getEntity(Language::class, ['code' => 'fr_FR']);
        $enLocalization = $this->getEntity(Localization::class, ['id' => 1, 'language' => $languageEn]);
        $frLocalization = $this->getEntity(Localization::class, ['id' => 2, 'language' => $languageFr]);

        $this->configureUserLocalizationManagerWithLocalizations([$enLocalization, $frLocalization]);

        $entity = $this->configureSluggableInterfaceDataWithSlugs([
            $this->getEntity(Slug::class, ['url' => self::BASE_SLUG]),
            $this->getEntity(Slug::class, ['url' => self::EN_SLUG, 'localization' => $enLocalization]),
            $this->getEntity(Slug::class, ['url' => self::FR_SLUG, 'localization' => $frLocalization])
        ]);

        $this->configureConfigManager(Configuration::DIRECT_URL, true);

        $this->validator->expects($this->any())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $baseUrl = 'http://domain.com/base_slug';
        $enUrl = 'http://domain.com/en_slug';
        $frUrl = 'http://domain.com/fr_slug';

        $this->urlGenerator->expects($this->exactly(3))
            ->method('getAbsoluteUrl')
            ->withConsecutive([self::BASE_SLUG], [self::EN_SLUG], [self::FR_SLUG])
            ->willReturnOnConsecutiveCalls($baseUrl, $enUrl, $frUrl);

        $expectedData = [
            new AlternateUrl($baseUrl),
            new AlternateUrl($enUrl, $enLocalization),
            new AlternateUrl($frUrl, $frLocalization)
        ];

        $this->assertEquals($expectedData, $this->dataProvider->getAlternates($entity));
    }

    public function testGetAlternatesWithSlugAwareInterfaceDataAndNotValidLocalization()
    {
        $enLanguageCode = 'en';
        $notValidLanguageCode = 'fr_FRA';

        $languageEn = $this->getEntity(Language::class, ['code' => $enLanguageCode]);
        $languageFr = $this->getEntity(Language::class, ['code' => $notValidLanguageCode]);
        $enLocalization = $this->getEntity(Localization::class, ['id' => 1, 'language' => $languageEn]);
        $notValidLanguageLocalization = $this->getEntity(Localization::class, [
            'id' => 2,
            'language' => $languageFr
        ]);

        $this->configureUserLocalizationManagerWithLocalizations([$enLocalization, $notValidLanguageLocalization]);

        $this->configureValidatorWithOneValidAndOneNotValidLanguageCode($enLanguageCode, $notValidLanguageCode);

        $entity = $this->configureSlugAwareDataWithSlugs([
            $this->getEntity(Slug::class, ['url' => self::BASE_SLUG]),
            $this->getEntity(Slug::class, ['url' => self::EN_SLUG, 'localization' => $enLocalization]),
            $this->getEntity(Slug::class, [
                'url' => '/not-valid-fr-fra-slug',
                'localization' => $notValidLanguageLocalization
            ])
        ]);

        $this->configureConfigManager(Configuration::DIRECT_URL, true);

        $baseUrl = 'http://domain.com/base_slug';
        $enUrl = 'http://domain.com/en_slug';

        $this->urlGenerator->expects($this->exactly(2))
            ->method('getAbsoluteUrl')
            ->withConsecutive([self::BASE_SLUG], [self::EN_SLUG])
            ->willReturnOnConsecutiveCalls($baseUrl, $enUrl);

        $expectedData = [
            new AlternateUrl($baseUrl),
            new AlternateUrl($enUrl, $enLocalization),
        ];

        $this->assertEquals($expectedData, $this->dataProvider->getAlternates($entity));
    }

    public function testGetAlternatesWithSluggableInterfaceDataAndNotValidLocalizationAndDirectUrlSupported()
    {
        $this->configureConfigManager(Configuration::DIRECT_URL, true);

        $enLanguageCode = 'en';
        $notValidLanguageCode = 'fr_FRA';
        $languageEn = $this->getEntity(Language::class, ['code' => $enLanguageCode]);
        $languageFr = $this->getEntity(Language::class, ['code' => $notValidLanguageCode]);
        $enLocalization = $this->getEntity(Localization::class, ['id' => 1, 'language' => $languageEn]);
        $notValidLanguageLocalization = $this->getEntity(Localization::class, [
            'id' => 2,
            'language' => $languageFr
        ]);

        $this->configureUserLocalizationManagerWithLocalizations([$enLocalization, $notValidLanguageLocalization]);

        $this->configureValidatorWithOneValidAndOneNotValidLanguageCode($enLanguageCode, $notValidLanguageCode);

        $entity = $this->configureSluggableInterfaceDataWithSlugs([
            $this->getEntity(Slug::class, ['url' => self::BASE_SLUG]),
            $this->getEntity(Slug::class, ['url' => self::EN_SLUG, 'localization' => $enLocalization]),
            $this->getEntity(Slug::class, [
                'url' => '/not-valid-fr-fra-slug',
                'localization' => $notValidLanguageLocalization
            ])
        ]);

        $baseUrl = 'http://domain.com/base_slug';
        $enUrl = 'http://domain.com/en_slug';

        $this->urlGenerator->expects($this->exactly(2))
            ->method('getAbsoluteUrl')
            ->withConsecutive([self::BASE_SLUG], [self::EN_SLUG])
            ->willReturnOnConsecutiveCalls($baseUrl, $enUrl);

        $expectedData = [
            new AlternateUrl($baseUrl),
            new AlternateUrl($enUrl, $enLocalization),
        ];

        $this->assertEquals($expectedData, $this->dataProvider->getAlternates($entity));
    }

    private function configureValidatorWithOneValidAndOneNotValidLanguageCode(
        string $validLanguageCode,
        string $notValidLanguageCode
    ): void {
        $notEmptyViolationList = $this->createMock(ConstraintViolationListInterface::class);
        $notEmptyViolationList->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $this->validator->expects($this->any())
            ->method('validate')
            ->withConsecutive(
                [$validLanguageCode, $this->isInstanceOf(Locale::class)],
                [$notValidLanguageCode, $this->isInstanceOf(Locale::class)]
            )
            ->willReturnOnConsecutiveCalls(
                $this->createMock(ConstraintViolationListInterface::class),
                $notEmptyViolationList
            );
    }

    private function configureSluggableInterfaceDataWithSlugs(array $slugs): SluggableInterface
    {
        $entity = $this->createMock(SluggableInterface::class);
        $entity->expects($this->any())
            ->method('getSlugs')
            ->willReturn($slugs);

        return $entity;
    }

    private function configureSlugAwareDataWithSlugs(array $slugs): SlugAwareInterface
    {
        $entity = $this->createMock(SlugAwareInterface::class);
        $entity->expects($this->any())
            ->method('getSlugs')
            ->willReturn($slugs);

        return $entity;
    }

    private function configureUserLocalizationManagerWithLocalizations(array $localizations): void
    {
        $this->userLocalizationManager->expects($this->once())
            ->method('getEnabledLocalizations')
            ->willReturn($localizations);
    }

    private function configureConfigManager(string $canonicalUrlType, bool $enableDirectUrl): void
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['oro_redirect.canonical_url_type', false, false, null, $canonicalUrlType],
                ['oro_redirect.enable_direct_url', false, false, null, $enableDirectUrl]
            ]);
    }
}
