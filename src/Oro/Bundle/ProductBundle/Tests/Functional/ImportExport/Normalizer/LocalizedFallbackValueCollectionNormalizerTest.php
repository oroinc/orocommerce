<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LocalizedFallbackValueCollectionNormalizerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
    }

    private function getNormalizer(string $localizedFallbackValueClass): LocalizedFallbackValueCollectionNormalizer
    {
        return new LocalizedFallbackValueCollectionNormalizer(
            self::getContainer()->get('doctrine'),
            $localizedFallbackValueClass,
            Localization::class
        );
    }

    private function getLocalizedValue(
        ?string $fallback,
        ?string $stringValue,
        ?string $textValue,
        ?string $localizationName
    ): LocalizedFallbackValue {
        $entity = new LocalizedFallbackValue();
        if (null !== $fallback) {
            $entity->setFallback($fallback);
        }
        if (null !== $stringValue) {
            $entity->setString($stringValue);
        }
        if (null !== $textValue) {
            $entity->setText($textValue);
        }
        if ('' === $localizationName) {
            $entity->setLocalization($this->getLocalization());
        } elseif (null !== $localizationName) {
            $entity->setLocalization($this->getLocalization($localizationName));
        }

        return $entity;
    }

    private function getLocalization(?string $name = null): Localization
    {
        $entity = new Localization();
        if (null !== $name) {
            $entity->setName($name);
        }

        return $entity;
    }

    public function testNormalizeWithoutLocalization(): void
    {
        $actualData = new ArrayCollection([
            $this->getLocalizedValue('system', 'value', null, null)
        ]);
        $expectedData = [
            'default' => ['fallback' => 'system', 'string' => 'value', 'text' => null]
        ];

        $normalizer = $this->getNormalizer(LocalizedFallbackValue::class);
        self::assertEquals($expectedData, $normalizer->normalize($actualData));
    }

    public function testNormalizeLocalizationWithoutName(): void
    {
        $actualData = new ArrayCollection([
            $this->getLocalizedValue('system', null, 'value', '')
        ]);
        $expectedData = [
            'default' => ['fallback' => 'system', 'string' => null, 'text' => 'value']
        ];

        $normalizer = $this->getNormalizer(LocalizedFallbackValue::class);
        self::assertEquals($expectedData, $normalizer->normalize($actualData));
    }

    public function testNormalizeLocalizationWithName(): void
    {
        $actualData = new ArrayCollection([
            $this->getLocalizedValue('system', null, 'value', 'English')
        ]);
        $expectedData = [
            'English' => ['fallback' => 'system', 'string' => null, 'text' => 'value']
        ];

        $normalizer = $this->getNormalizer(LocalizedFallbackValue::class);
        self::assertEquals($expectedData, $normalizer->normalize($actualData));
    }

    public function testNormalizeMixed(): void
    {
        $actualData = new ArrayCollection([
            $this->getLocalizedValue('system', null, 'value', 'English'),
            $this->getLocalizedValue('system', 'value', null, 'English (Canada)'),
            $this->getLocalizedValue('system', null, 'value', '')
        ]);
        $expectedData = [
            'English' => ['fallback' => 'system', 'string' => null, 'text' => 'value'],
            'English (Canada)' => ['fallback' => 'system', 'string' => 'value', 'text' => null],
            'default' => ['fallback' => 'system', 'string' => null, 'text' => 'value'],
        ];

        $normalizer = $this->getNormalizer(LocalizedFallbackValue::class);
        self::assertEquals($expectedData, $normalizer->normalize($actualData));
    }

    public function testDenormalizeNotArray(): void
    {
        $class = LocalizedFallbackValue::class;
        $actualData = 'value';
        $expectedData = new ArrayCollection([]);

        $normalizer = $this->getNormalizer(LocalizedFallbackValue::class);
        self::assertEquals($expectedData, $normalizer->denormalize($actualData, $class));
    }

    public function testDenormalizeWrongType(): void
    {
        $class = LocalizedFallbackValue::class;
        $actualData = [];
        $expectedData = new ArrayCollection([]);

        $normalizer = $this->getNormalizer(LocalizedFallbackValue::class);
        self::assertEquals($expectedData, $normalizer->denormalize($actualData, $class));
    }

    public function testDenormalizeWithoutLocalization(): void
    {
        $class = sprintf('ArrayCollection<%s>', LocalizedFallbackValue::class);
        $actualData = [
            'default' => ['fallback' => 'system', 'string' => 'value', 'text' => null]
        ];
        $expectedData = new ArrayCollection([
            'default' => $this->getLocalizedValue('system', 'value', null, null)
        ]);

        $normalizer = $this->getNormalizer(LocalizedFallbackValue::class);
        self::assertEquals($expectedData, $normalizer->denormalize($actualData, $class));
    }

    public function testDenormalizeLocalizationWithNameAndDefaultMissing(): void
    {
        $class = sprintf('ArrayCollection<%s>', LocalizedFallbackValue::class);
        $actualData = [
            'English' => ['fallback' => 'system', 'string' => 'value']
        ];
        $expectedData = new ArrayCollection([
            'default' => $this->getLocalizedValue(null, null, null, null),
            'English' => $this->getLocalizedValue('system', 'value', null, 'English')
        ]);

        $normalizer = $this->getNormalizer(LocalizedFallbackValue::class);
        self::assertEquals($expectedData, $normalizer->denormalize($actualData, $class));
    }

    public function testDenormalizeMixed(): void
    {
        $class = sprintf('ArrayCollection<%s>', LocalizedFallbackValue::class);
        $actualData = [
            'default' => ['fallback' => 'system', 'string' => 'value', 'text' => null],
            'English' => ['string' => 'value'],
            'English (Canada)' => ['fallback' => 'parent_localization', 'text' => 'value'],
        ];
        $expectedData = new ArrayCollection([
            'default' => $this->getLocalizedValue('system', 'value', null, null),
            'English' => $this->getLocalizedValue(null, 'value', null, 'English'),
            'English (Canada)' => $this->getLocalizedValue('parent_localization', null, 'value', 'English (Canada)')
        ]);

        $normalizer = $this->getNormalizer(LocalizedFallbackValue::class);
        self::assertEquals($expectedData, $normalizer->denormalize($actualData, $class));
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization(ArrayCollection|array $data, bool $expected, array $context = []): void
    {
        $normalizer = $this->getNormalizer(ProductName::class);
        self::assertEquals($expected, $normalizer->supportsNormalization($data, null, $context));
        // trigger caches
        self::assertEquals($expected, $normalizer->supportsNormalization($data, null, $context));
    }

    public function supportsNormalizationDataProvider(): array
    {
        return [
            'not a collection' => [[], false],
            'collection' => [new ArrayCollection(), false],
            'not existing collection field' => [
                new ArrayCollection(),
                false,
                ['entityName' => Product::class, 'fieldName' => 'names1'],
            ],
            'not supported field' => [
                new ArrayCollection(),
                false,
                ['entityName' => Product::class, 'fieldName' => 'unitPrecisions'],
            ],
            'supported field' => [
                new ArrayCollection(),
                true,
                ['entityName' => Product::class, 'fieldName' => 'names'],
            ],
        ];
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization(
        ArrayCollection $data,
        string $class,
        bool $expected,
        array $context = []
    ): void {
        $normalizer = $this->getNormalizer(ProductName::class);
        self::assertEquals($expected, $normalizer->supportsDenormalization($data, $class, null, $context));
        // trigger caches
        self::assertEquals($expected, $normalizer->supportsDenormalization($data, $class, null, $context));
    }

    public function supportsDenormalizationDataProvider(): array
    {
        return [
            'not a collection' => [new ArrayCollection(), Product::class, false],
            'not existing collection field' => [
                new ArrayCollection(),
                'ArrayCollection<Oro\Bundle\ProductBundle\Entity\Product>',
                false,
                ['entityName' => Product::class, 'fieldName' => 'names1'],
            ],
            'not supported field' => [
                new ArrayCollection(),
                'ArrayCollection<Oro\Bundle\ProductBundle\Entity\Product>',
                false,
                ['entityName' => Product::class, 'fieldName' => 'unitPrecisions'],
            ],
            'supported field' => [
                new ArrayCollection(),
                'ArrayCollection<Oro\Bundle\ProductBundle\Entity\Product>',
                true,
                ['entityName' => Product::class, 'fieldName' => 'names'],
            ],
            'namespace' => [
                new ArrayCollection(),
                'Doctrine\Common\Collections\ArrayCollection<Oro\Bundle\ProductBundle\Entity\Product>',
                true,
                ['entityName' => Product::class, 'fieldName' => 'names'],
            ],
            'not supported class' => [
                new ArrayCollection(),
                'Doctrine\Common\Collections\ArrayCollection<Oro\Bundle\ProductBundle\Entity\ProductUnit>',
                true,
                ['entityName' => Product::class, 'fieldName' => 'names'],
            ],
        ];
    }
}
