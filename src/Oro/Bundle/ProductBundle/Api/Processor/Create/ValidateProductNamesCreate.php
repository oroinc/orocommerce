<?php

namespace Oro\Bundle\ProductBundle\Api\Processor\Create;

use Oro\Bundle\ApiBundle\Processor\Create\JsonApi\ValidateRequestData;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder;

class ValidateProductNamesCreate extends ValidateRequestData
{
    const NAMES_RELATION = 'names';

    /**
     * @param array $data
     * @param string $pointer
     */
    protected function validateRelationships(array $data, $pointer)
    {
        return $this->validateRequired($data, self::NAMES_RELATION, $pointer)
            && $this->validateArray($data[self::NAMES_RELATION], JsonApiDocumentBuilder::DATA, $pointer, true);
    }
}
