<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource\JsonApi\ValidateRequestData;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;

/**
 * Validates the request data for "add to cart" sub-resource.
 */
class ValidateAddShoppingListItemsRequestData extends ValidateRequestData
{
    public function __construct()
    {
        // override the constructor to disable arguments from the parent class constructor
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function validateRequestData(ChangeSubresourceContext $context): array
    {
        $errors = parent::validateRequestData($context);
        if (empty($errors)) {
            $requestData = $context->getRequestData();
            foreach ($requestData[JsonApiDoc::DATA] as $itemIndex => $item) {
                if (\array_key_exists(JsonApiDoc::ID, $item)) {
                    $context->addError($this->createIdValidationError($itemIndex));
                }
            }
        }

        return $errors;
    }

    private function createIdValidationError(int $itemIndex): Error
    {
        return Error::createValidationError(Constraint::REQUEST_DATA, 'The identifier should not be specified')
            ->setSource(
                ErrorSource::createByPointer(\sprintf('/%s/%d/%s', JsonApiDoc::DATA, $itemIndex, JsonApiDoc::ID))
            );
    }
}
