<?php

namespace Oro\Bundle\ProductBundle\Processor\Create;

use Oro\Bundle\ApiBundle\Processor\Create\JsonApi\ValidateRequestData;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;

class ValidateRelatedProduct extends ValidateRequestData
{
    /**
     * {@inheritdoc}
     */
    protected function validateAttributesAndRelationships(array $data, $pointer)
    {
        if ($this->validateRequired($data, JsonApiDoc::RELATIONSHIPS, $pointer)) {
            $this->validateArray($data, JsonApiDoc::RELATIONSHIPS, $pointer, true, true);
            $this->validateRelationships(
                $data[JsonApiDoc::RELATIONSHIPS],
                $this->buildPointer($pointer, JsonApiDoc::RELATIONSHIPS)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateRelationships(array $data, $pointer)
    {
        return $this->validateRequired($data, 'product', $pointer)
            && $this->validateArray($data['product'], JsonApiDoc::DATA, $pointer, true)
            && $this->validateRequired($data, 'relatedItem', $pointer)
            && $this->validateArray($data['relatedItem'], JsonApiDoc::DATA, $pointer, true);
    }
}
