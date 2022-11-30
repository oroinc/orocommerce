<?php

namespace Oro\Bundle\TaxBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes value for zipCodes field of TaxJurisdiction entity.
 */
class ComputeTaxJurisdictionZipCodes implements ProcessorInterface
{
    private const ZIP_CODES_FIELD_NAME = 'zipCodes';

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        if (!$context->isFieldRequested(self::ZIP_CODES_FIELD_NAME, $data)) {
            return;
        }

        $zipCodes = [];
        foreach ($data['_zipCodes'] as $item) {
            $zipCode = $item['zipCode'];
            $zipCodes[] = $zipCode
                ? ['from' => $zipCode, 'to' => null]
                : ['from' => $item['zipRangeStart'], 'to' => $item['zipRangeEnd']];
        }
        $data[self::ZIP_CODES_FIELD_NAME] = $zipCodes;
        $context->setData($data);
    }
}
