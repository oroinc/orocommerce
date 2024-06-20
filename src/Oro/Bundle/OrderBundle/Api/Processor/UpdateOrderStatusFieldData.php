<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes the order status from the submitted data
 * when "Enable External Status Management" configuration option is disabled.
 */
class UpdateOrderStatusFieldData implements ProcessorInterface
{
    private OrderConfigurationProviderInterface $configurationProvider;

    public function __construct(OrderConfigurationProviderInterface $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var FormContext $context */

        if ($this->configurationProvider->isExternalStatusManagementEnabled()) {
            return;
        }

        $fieldName = $context->getConfig()?->findFieldNameByPropertyPath('internal_status');
        if (!$fieldName) {
            return;
        }
        $data = $context->getRequestData();
        if (isset($data[JsonApiDoc::DATA][JsonApiDoc::RELATIONSHIPS][$fieldName])) {
            unset($data[JsonApiDoc::DATA][JsonApiDoc::RELATIONSHIPS][$fieldName]);
            $context->setRequestData($data);
        }
    }
}
