<?php

namespace Oro\Bundle\CMSBundle\ImportExport\Normalizer;

use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizationCodeFormatter;
use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizer as BaseNormalizer;

/**
 * Adds support of "wysiwyg" field for the LocalizedFallbackValue entity.
 */
class LocalizedFallbackValueCollectionNormalizer extends BaseNormalizer
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $result = parent::normalize($object, $format, $context);

        foreach ($object as $item) {
            $key = LocalizationCodeFormatter::formatName($item->getLocalization());

            $result[$key]['wysiwyg'] = EntityPropertyInfo::methodExists($item, 'getWysiwyg')
                ? $item->getWysiwyg()
                : null;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = []): object
    {
        $result = parent::denormalize($data, $type, $format, $context);
        if ($result->isEmpty()) {
            return $result;
        }

        foreach ($data as $localizationName => $item) {
            if (array_key_exists('wysiwyg', $item) && $result->containsKey($localizationName)) {
                /** @var LocalizedFallbackValue $object */
                $object = $result->get($localizationName);

                if (EntityPropertyInfo::methodExists($object, 'setWysiwyg')) {
                    $object->setWysiwyg((string)$item['wysiwyg']);
                }
            }
        }

        return $result;
    }
}
