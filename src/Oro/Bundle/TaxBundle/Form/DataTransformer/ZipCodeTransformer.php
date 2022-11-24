<?php

namespace Oro\Bundle\TaxBundle\Form\DataTransformer;

use Oro\Bundle\TaxBundle\Entity\ZipCode;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * The data transformer for the tax jurisdiction zip code form type.
 */
class ZipCodeTransformer implements DataTransformerInterface
{
    /**
     * {@inheritDoc}
     */
    public function transform($value)
    {
        /** @var ZipCode|null $value */

        if (null === $value) {
            return null;
        }

        if ($value->isSingleZipCode()) {
            $value->setZipRangeStart($value->getZipCode());
            $value->setZipCode(null);
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform($value)
    {
        /** @var ZipCode|null $value */

        if (null === $value) {
            return null;
        }

        $zipRangeStart = $value->getZipRangeStart();
        $zipRangeEnd = $value->getZipRangeEnd();
        if ($zipRangeStart === $zipRangeEnd
            || ($zipRangeStart && !$zipRangeEnd)
            || (!$zipRangeStart && $zipRangeEnd)
        ) {
            $value->setZipCode($zipRangeStart ?: $zipRangeEnd);
            $value->setZipRangeStart(null);
            $value->setZipRangeEnd(null);
        }

        return $value;
    }
}
