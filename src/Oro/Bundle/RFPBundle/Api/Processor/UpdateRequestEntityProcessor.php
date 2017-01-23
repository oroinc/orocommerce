<?php

namespace Oro\Bundle\RFPBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class UpdateRequestEntityProcessor implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$context instanceof UpdateContext) {
            return;
        }

        $requestData = $context->getRequestData();

        if (!$requestData) {
            return;
        }

        $this->processRequestData($requestData);
        $context->setRequestData($requestData);
    }

    /**
     * @param array $requestData
     */
    protected function processRequestData(array &$requestData)
    {
        foreach ($this->getDisabledAttribute() as $attribute) {
            if (array_key_exists($attribute, $requestData)) {
                unset($requestData[$attribute]);
            }
        }
    }

    /**
     * @return array
     */
    protected function getDisabledAttribute()
    {
        return [
            'customer_status',
            'internal_status',
            'createdAt',
            'updatedAt',
            'requestAdditionalNotes'
        ];
    }
}
