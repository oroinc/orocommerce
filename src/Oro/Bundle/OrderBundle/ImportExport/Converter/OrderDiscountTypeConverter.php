<?php

namespace Oro\Bundle\OrderBundle\ImportExport\Converter;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataConverterInterface;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataErrorConverterInterface;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataReverseConverterInterface;

/**
 * Converts a value for "type" attribute of order discount entity.
 */
class OrderDiscountTypeConverter implements
    ComplexDataConverterInterface,
    ComplexDataReverseConverterInterface,
    ComplexDataErrorConverterInterface
{
    private const string TYPE = 'type';
    private const string TYPE_API = 'orderDiscountType';
    private const string TYPE_PREFIX = 'oro_order_discount_item_type_';

    #[\Override]
    public function convert(array $item, mixed $sourceData): array
    {
        if (
            !empty($item[self::ENTITY][JsonApiDoc::ATTRIBUTES][self::TYPE_API])
            && \is_string($item[self::ENTITY][JsonApiDoc::ATTRIBUTES][self::TYPE_API])
            && !str_starts_with($item[self::ENTITY][JsonApiDoc::ATTRIBUTES][self::TYPE_API], self::TYPE_PREFIX)
        ) {
            $item[self::ENTITY][JsonApiDoc::ATTRIBUTES][self::TYPE_API] =
                self::TYPE_PREFIX . $item[self::ENTITY][JsonApiDoc::ATTRIBUTES][self::TYPE_API];
        }

        return $item;
    }

    #[\Override]
    public function reverseConvert(array $item, object $sourceEntity): array
    {
        if (isset($item[self::TYPE]) && str_starts_with($item[self::TYPE], self::TYPE_PREFIX)) {
            $item[self::TYPE] = substr($item[self::TYPE], \strlen(self::TYPE_PREFIX));
        }

        return $item;
    }

    #[\Override]
    public function convertError(string $error, ?string $propertyPath): string
    {
        if (self::TYPE === $propertyPath) {
            return str_replace(self::TYPE_PREFIX, '', $error);
        }

        return $error;
    }
}
