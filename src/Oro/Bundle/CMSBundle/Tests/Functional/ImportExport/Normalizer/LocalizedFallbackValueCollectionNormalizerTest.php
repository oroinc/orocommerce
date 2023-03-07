<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\ImportExport\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CMSBundle\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizer;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class LocalizedFallbackValueCollectionNormalizerTest extends WebTestCase
{
    private LocalizedFallbackValueCollectionNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->normalizer = new LocalizedFallbackValueCollectionNormalizer(
            $this->getContainer()->get('doctrine'),
            LocalizedFallbackValue::class,
            Localization::class
        );
    }

    private function getLocalizedValue(
        ?string $fallback,
        ?string $value,
        ?string $valueType,
        ?string $localizationName
    ): LocalizedFallbackValue {
        $entity = new LocalizedFallbackValue();
        if (null !== $fallback) {
            $entity->setFallback($fallback);
        }
        if (null !== $value) {
            switch ($valueType) {
                case 'string':
                    $entity->setString($value);
                    break;
                case 'text':
                    $entity->setText($value);
                    break;
                case 'wysiwyg':
                    $entity->setWysiwyg($value);
                    break;
                default:
                    throw new \LogicException(sprintf('The "%s" value is not supported.', $valueType));
            }
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
            $this->getLocalizedValue('system', 'value', 'wysiwyg', null)
        ]);
        $expectedData = [
            'default' => ['fallback' => 'system', 'string' => null, 'text' => null, 'wysiwyg' => 'value']
        ];

        $this->assertEquals($expectedData, $this->normalizer->normalize($actualData));
    }

    public function testNormalizeLocalizationWithoutName(): void
    {
        $actualData = new ArrayCollection([
            $this->getLocalizedValue('system', 'value', 'wysiwyg', '')
        ]);
        $expectedData = [
            'default' => ['fallback' => 'system', 'string' => null, 'text' => null, 'wysiwyg' => 'value']
        ];

        $this->assertEquals($expectedData, $this->normalizer->normalize($actualData));
    }

    public function testNormalizeLocalizationWithName(): void
    {
        $actualData = new ArrayCollection([
            $this->getLocalizedValue('system', 'value', 'wysiwyg', 'English')
        ]);
        $expectedData = [
            'English' => ['fallback' => 'system', 'string' => null, 'text' => null, 'wysiwyg' => 'value']
        ];

        $this->assertEquals($expectedData, $this->normalizer->normalize($actualData));
    }

    public function testNormalizeMixed(): void
    {
        $actualData = new ArrayCollection([
            $this->getLocalizedValue('system', 'value', 'text', 'English'),
            $this->getLocalizedValue('system', 'value', 'string', 'English (Canada)'),
            $this->getLocalizedValue('system', 'value', 'wysiwyg', 'French (France)'),
            $this->getLocalizedValue('system', 'value', 'text', ''),
        ]);
        $expectedData = [
            'English' => ['fallback' => 'system', 'string' => null, 'text' => 'value', 'wysiwyg' => null],
            'English (Canada)' => ['fallback' => 'system', 'string' => 'value', 'text' => null, 'wysiwyg' => null],
            'French (France)' => ['fallback' => 'system', 'string' => null, 'text' => null, 'wysiwyg' => 'value'],
            'default' => ['fallback' => 'system', 'string' => null, 'text' => 'value', 'wysiwyg' => null]
        ];

        $this->assertEquals($expectedData, $this->normalizer->normalize($actualData));
    }

    public function testDenormalizeNotArray(): void
    {
        $class = LocalizedFallbackValue::class;
        $actualData = 'value';
        $expectedData = new ArrayCollection([]);

        $this->assertEquals($expectedData, $this->normalizer->denormalize($actualData, $class));
    }

    public function testDenormalizeWrongType(): void
    {
        $class = LocalizedFallbackValue::class;
        $actualData = [];
        $expectedData = new ArrayCollection([]);

        $this->assertEquals($expectedData, $this->normalizer->denormalize($actualData, $class));
    }

    public function testDenormalizeWithoutLocalization(): void
    {
        $class = sprintf('ArrayCollection<%s>', LocalizedFallbackValue::class);
        $actualData = [
            'default' => ['fallback' => 'system', 'string' => null, 'text' => null, 'wysiwyg' => 'value']
        ];
        $expectedData = new ArrayCollection([
            'default' => $this->getLocalizedValue('system', 'value', 'wysiwyg', null)
        ]);

        $this->assertEquals($expectedData, $this->normalizer->denormalize($actualData, $class));
    }

    public function testDenormalizeLocalizationWithNameAndDefaultMissing(): void
    {
        $class = sprintf('ArrayCollection<%s>', LocalizedFallbackValue::class);
        $actualData = [
            'English' => ['fallback' => 'system', 'string' => null, 'text' => null, 'wysiwyg' => 'value']
        ];
        $expectedData = new ArrayCollection([
            'default' => $this->getLocalizedValue(null, null, null, null),
            'English' => $this->getLocalizedValue('system', 'value', 'wysiwyg', 'English')
        ]);

        $this->assertEquals($expectedData, $this->normalizer->denormalize($actualData, $class));
    }

    public function testDenormalizeMixed(): void
    {
        $class = sprintf('ArrayCollection<%s>', LocalizedFallbackValue::class);
        $actualData = [
            'default' => ['fallback' => 'system', 'string' => 'value', 'text' => null, 'wysiwyg' => null],
            'English' => ['string' => 'value', 'wysiwyg' => null],
            'English (Canada)' => ['fallback' => 'parent_localization', 'text' => 'value'],
            'French (France)' => [
                'fallback' => 'parent_localization',
                'string' => null,
                'text' => null,
                'wysiwyg' => 'value'
            ]
        ];
        $defaultValue = $this->getLocalizedValue('system', 'value', 'string', null);
        $defaultValue->setText('');
        $defaultValue->setWysiwyg('');
        $englishValue = $this->getLocalizedValue(null, 'value', 'string', 'English');
        $englishValue->setWysiwyg('');
        $expectedData = new ArrayCollection([
            'default' => $defaultValue,
            'English' => $englishValue,
            'English (Canada)' => $this->getLocalizedValue('parent_localization', 'value', 'text', 'English (Canada)'),
            'French (France)' => $this->getLocalizedValue('parent_localization', 'value', 'wysiwyg', 'French (France)')
        ]);

        $this->assertEquals($expectedData, $this->normalizer->denormalize($actualData, $class));
    }
}
