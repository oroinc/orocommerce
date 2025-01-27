<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource\GetSubresourceContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Updates the response for the sub-resource that validates whether Checkout entity is ready for payment
 * for case the checkout is not ready for payment.
 */
class UpdateValidatePaymentSubresourceResponse implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var GetSubresourceContext $context */

        if ($context->getResponseStatusCode() !== Response::HTTP_BAD_REQUEST) {
            return;
        }

        $responseData = $context->getResult();
        if (!isset($responseData[JsonApiDoc::ERRORS])) {
            return;
        }

        $context->setResult([
            JsonApiDoc::META => [
                'message' => 'The checkout is not ready for payment.',
                'paymentUrl' => null,
                'errors' => $responseData[JsonApiDoc::ERRORS]
            ]
        ]);
        $context->setResponseStatusCode(Response::HTTP_OK);
    }
}
