<?php

namespace Oro\Bundle\VisibilityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Disables includes for Visibility entities.
 */
class DisableIncludesForVisibilityEntities implements ProcessorInterface
{
    public function process(ContextInterface $context): void
    {
        /** @var FormContext $context */

        $requestData = $context->getRequestData();
        if ($requestData && \array_key_exists(JsonApiDoc::INCLUDED, $requestData)) {
            $context->addError(
                Error::createValidationError(
                    Constraint::REQUEST_DATA,
                    'The included data are not supported for this resource type.'
                )->setSource(ErrorSource::createByPointer('/' . JsonApiDoc::INCLUDED))
            );
        }
    }
}
