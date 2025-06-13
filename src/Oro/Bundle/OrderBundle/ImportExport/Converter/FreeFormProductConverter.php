<?php

namespace Oro\Bundle\OrderBundle\ImportExport\Converter;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataConverterInterface;

/**
 * Adds "freeFormProduct" attribute to order line item entity.
 */
class FreeFormProductConverter implements ComplexDataConverterInterface
{
    private const string PRODUCT_NAME = 'productName';

    #[\Override]
    public function convert(array $item, mixed $sourceData): array
    {
        if (!isset($item[self::ENTITY][JsonApiDoc::RELATIONSHIPS]['product'][JsonApiDoc::DATA])
            && isset($item[self::ENTITY][JsonApiDoc::ATTRIBUTES])
        ) {
            $attributes = $item[self::ENTITY][JsonApiDoc::ATTRIBUTES];
            if (\array_key_exists(self::PRODUCT_NAME, $attributes)) {
                $item[self::ENTITY][JsonApiDoc::ATTRIBUTES]['freeFormProduct'] = $attributes[self::PRODUCT_NAME];
                unset($item[self::ENTITY][JsonApiDoc::ATTRIBUTES][self::PRODUCT_NAME]);
            }
        }

        return $item;
    }
}
