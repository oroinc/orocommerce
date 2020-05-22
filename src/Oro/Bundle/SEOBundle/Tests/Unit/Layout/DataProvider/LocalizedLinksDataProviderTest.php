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

    const BASE_SLUG = '/base-slug';
    const EN_SLUG = '/en-slug';
    const FR_SLUG = '/fr-slug';

    /**
     * @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $urlGenerator;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var UserLocalizationManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $userLocalizationManager;

    /**
     * @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $validator;

    /**
     * @var LocalizedLinksDataProvider
     */
    private $dataProvider;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->getMockBuilder(CanonicalUrlGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userLocalizationManager = $this->getMockBuilder(UserLocalizationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

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
        /** @var SluggableInterface $entity */
        $entity = $this->createMock(SlugAwareInterface::class);

        $this->configureUserLocalizationManagerWithLocalizations([$this->getEntity(Localization::class)]);

        $this->urlGenerator
            ->expects($this->never())
            ->method('getAbsoluteUrl');

        $this->assertEmpty($this->dataProvider->getAlternates($entity));
    }

    public function testGetAlternatesWithOneEnabledLocalizationAndSluggableInterfaceAndDirectUrlSupported()
    {
        /** @var SluggableInterface $entity */
        $entity = $this->createMock(SluggableInterface::class);

        $this->configureUserLocalizationManagerWithLocalizations([$this->getEntity(Localization::class)]);

        $this->configureConfigManager(Configuration::DIRECT_URL, true);

        $this->urlGenerator
            ->expects($this->never())
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

        $this->validator
            ->expects($this->any())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->configureConfigManager(Configuration::DIRECT_URL, true);

        $baseUrl = 'http://domain.com/base_slug';
        $enUrl = 'http://domain.com/en_slug';
        $frUrl = 'http://domain.com/fr_slug';

        $this->urlGenerator
            ->expects($this->exactly(3))
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
     * @param string $canonicalUrlType
     * @param bool $enableDirectUrl
     */
    public function testGetAlternatesWithSluggableInterfaceDataAndDirectUrlNotSupportedAndManyLocalizationsEnabled(
        $canonicalUrlType,
        $enableDirectUrl
    ) {
        $languageEn = $this->getEntity(Language::class, ['code' => 'en']);
        $languageFr = $this->getEntity(Language::class, ['code' => 'fr_FR']);
        $enLocalization = $this->getEntity(Localization::class, ['id' => 1, 'language' => $languageEn]);
        $frLocalization = $this->getEntity(Localization::class, ['id' => 2, 'language' => $languageFr]);

        $this->configureUserLocalizationManagerWithLocalizations([$enLocalization, $frLocalization]);

        $this->configureConfigManager($canonicalUrlType, $enableDirectUrl);

        $this->validator
            ->expects($this->any())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        /** @var SluggableInterface|\PHPUnit\Framework\MockObject\MockObject $entity */
        $entity = $this->createMock(SluggableInterface::class);

        $systemUrl = 'http://domain.com/some/entity/3';
        $this->urlGenerator
            ->expects($this->once())
            ->method('getSystemUrl')
            ->with($entity)
            ->willReturn($systemUrl);

        $expectedAlternateUrls = [new AlternateUrl($systemUrl)];

        $this->assertEquals($expectedAlternateUrls, $this->dataProvider->getAlternates($entity));
    }

    /**
     * @dataProvider directUrlNotSupportedDataProvider
     * @param string $canonicalUrlType
     * @param bool $enableDirectUrl
     */
    public function testGetAlternatesWithSluggableInterfaceDataAndDirectUrlNotSupportedAndOneLocalizationEnabled(
        $canonicalUrlType,
        $enableDirectUrl
    ) {
        $languageEn = $this->getEntity(Language::class, ['code' => 'en']);
        $enLocalization = $this->getEntity(Localization::class, ['id' => 1, 'language' => $languageEn]);

        $this->configureUserLocalizationManagerWithLocalizations([$enLocalization]);

        $this->configureConfigManager($canonicalUrlType, $enableDirectUrl);

        $this->validator
            ->expects($this->any())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        /** @var SluggableInterface|\PHPUnit\Framework\MockObject\MockObject $entity */
        $entity = $this->createMock(SluggableInterface::class);

        $this->assertEmpty($this->dataProvider->getAlternates($entity));
    }

    /**
     * @return array
     */
    public function directUrlNotSupportedDataProvider()
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

        $this->validator
            ->expects($this->any())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $baseUrl = 'http://domain.com/base_slug';
        $enUrl = 'http://domain.com/en_slug';
        $frUrl = 'http://domain.com/fr_slug';

        $this->urlGenerator
            ->expects($this->exactly(3))
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

        $this->urlGenerator
            ->expects($this->exactly(2))
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

        $this->urlGenerator
            ->expects($this->exactly(2))
            ->method('getAbsoluteUrl')
            ->withConsecutive([self::BASE_SLUG], [self::EN_SLUG])
            ->willReturnOnConsecutiveCalls($baseUrl, $enUrl);

        $expectedData = [
            new AlternateUrl($baseUrl),
            new AlternateUrl($enUrl, $enLocalization),
        ];

        $this->assertEquals($expectedData, $this->dataProvider->getAlternates($entity));
    }

    /**
     * @param string $validLanguageCode
     * @param string $notValidLanguageCode
     */
    private function configureValidatorWithOneValidAndOneNotValidLanguageCode($validLanguageCode, $notValidLanguageCode)
    {
        $notEmptyViolationList = $this->createMock(ConstraintViolationListInterface::class);
        $notEmptyViolationList
            ->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $this->validator
            ->expects($this->any())
            ->method('validate')
            ->withConsecutive(
                [
                    $validLanguageCode,
                    $this->isInstanceOf(Locale::class)
                ],
                [
                    $notValidLanguageCode,
                    $this->isInstanceOf(Locale::class)
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $this->createMock(ConstraintViolationListInterface::class),
                $notEmptyViolationList
            );
    }

    /**
     * @param array|Slug[] $slugs
     * @return SluggableInterface
     */
    private function configureSluggableInterfaceDataWithSlugs(array $slugs)
    {
        /** @var SluggableInterface|\PHPUnit\Framework\MockObject\MockObject $entity */
        $entity = $this->createMock(SluggableInterface::class);
        $entity
            ->expects($this->any())
            ->method('getSlugs')
            ->willReturn($slugs);

        return $entity;
    }

    /**
     * @param array|Slug[] $slugs
     * @return SlugAwareInterface
     */
    private function configureSlugAwareDataWithSlugs(array $slugs)
    {
        /** @var SlugAwareInterface|\PHPUnit\Framework\MockObject\MockObject $entity */
        $entity = $this->createMock(SlugAwareInterface::class);
        $entity
            ->expects($this->any())
            ->method('getSlugs')
            ->willReturn($slugs);

        return $entity;
    }

    /**
     * @param  array|Localization[] $localizations
     */
    private function configureUserLocalizationManagerWithLocalizations(array $localizations)
    {
        $this->userLocalizationManager
            ->expects($this->once())
            ->method('getEnabledLocalizations')
            ->willReturn($localizations);
    }

    /**
     * @param string $canonicalUrlType
     * @param bool $enableDirectUrl
     */
    private function configureConfigManager($canonicalUrlType, $enableDirectUrl)
    {
        $this->configManager
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['oro_redirect.canonical_url_type', false, false, null, $canonicalUrlType],
                ['oro_redirect.enable_direct_url', false, false, null, $enableDirectUrl]
            ]);
    }
}
