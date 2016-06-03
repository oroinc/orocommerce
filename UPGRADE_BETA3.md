Upgrade from beta.2
=========================

FallbackBundle:
---------------
- Entity `OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue` moved to `Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue`
- Form Type `OroB2B\Bundle\FallbackBundle\Form\Type\FallbackValueType` moved to `Oro\Bundle\LocaleBundle\Form\Type\FallbackValueType`
- Form Type `OroB2B\Bundle\FallbackBundle\Form\Type\FallbackPropertyType` moved to `Oro\Bundle\LocaleBundle\Form\Type\FallbackPropertyType`
- Form Type `OroB2B\Bundle\FallbackBundle\Form\Type\LocaleCollectionType` moved to `Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType`
- Form Type `OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedFallbackValueCollectionType` moved to `Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType`
- Form Type `OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedPropertyType` moved to `Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType`
- Form Type `OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type\Stub\LocaleCollectionTypeStub` moved to `Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocaleCollectionTypeStub`
- Model `OroB2B\Bundle\FallbackBundle\Model\FallbackType` moved to `Oro\Bundle\LocaleBundle\Model\FallbackType`
- DataTransformer `OroB2B\Bundle\FallbackBundle\Form\DataTransformer\MultipleValueTransformer` moved to `Oro\Bundle\LocaleBundle\Form\DataTransformer\MultipleValueTransformer`
- DataTransformer `OroB2B\Bundle\FallbackBundle\Form\DataTransformer\FallbackValueTransformer` moved to `Oro\Bundle\LocaleBundle\Form\DataTransformer\FallbackValueTransformer`
- DataTransformer `OroB2B\Bundle\FallbackBundle\Form\DataTransformer\LocalizedFallbackValueCollectionTransformer` moved to `Oro\Bundle\LocaleBundle\Form\DataTransformer\LocalizedFallbackValueCollectionTransformer`
- DataConverter `OroB2B\Bundle\FallbackBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter` moved to `Oro\Bundle\LocaleBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter`
- DataConverter `OroB2B\Bundle\FallbackBundle\ImportExport\DataConverter\PropertyPathTitleDataConverter` moved to `Oro\Bundle\LocaleBundle\ImportExport\DataConverter\PropertyPathTitleDataConverter`
- Normalizer `OroB2B\Bundle\FallbackBundle\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizer` moved to `Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizer`
- Strategy `OroB2B\Bundle\FallbackBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy` removed. Use `Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy` instead.
- Strategy `OroB2B\Bundle\WebsiteBundle\Translation\Strategy\LocaleFallbackStrategy` removed. Use `Oro\Bundle\LocaleBundle\Translation\Strategy\LocalizationFallbackStrategy` instead.
- Test Stub `OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub` moved to `Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub`
- Trait `OroB2B\Bundle\FallbackBundle\Entity\FallbackTrait` moved to `Oro\Bundle\LocaleBundle\Entity\FallbackTrait`

WebsiteBundle:
---------------
- Entity `OroB2B\Bundle\WebsiteBundle\Entity\Locale` removed. Use `Oro\Bundle\LocaleBundle\Entity\Localization` instead.
- Entity `OroB2B\Bundle\WebsiteBundle\Entity\Repository\LocaleRepository` removed. Use `Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository` instead.
- Entity `OroB2B\Bundle\WebsiteBundle\EventListener\ORM\LocaleListener` removed. Use `Oro\Bundle\LocaleBundle\EventListener\ORM\LocalizationListener` instead.
- Entity `OroB2B\Bundle\WebsiteBundle\Entity\Website` updated and now has unidirectional "ManyToMany" relation with `Oro\Bundle\LocaleBundle\Entity\Localization`
- Helper `OroB2B\Bundle\WebsiteBundle\Locale\LocaleHelper` removed. Use `Oro\Bundle\LocaleBundle\Helper\LocalizationHelper` instead.
- Migration `OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM\UpdateLocaleData` removed.