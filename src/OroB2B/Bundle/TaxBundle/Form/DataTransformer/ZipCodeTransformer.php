<?php

namespace OroB2B\Bundle\TaxBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use OroB2B\Bundle\TaxBundle\Entity\ZipCode;

class ZipCodeTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     * @param ZipCode $zipCode
     */
    public function transform($zipCode)
    {
        if (null === $zipCode) {
            return null;
        }

        if ($zipCode->isSingleZipCode()) {
            $zipCode
                ->setZipRangeStart($zipCode->getZipCode())
                ->setZipCode(null);
        }

        return $zipCode;
    }

    /**
     * {@inheritdoc}
     * @param ZipCode $zipCode
     */
    public function reverseTransform($zipCode)
    {
        if (null === $zipCode) {
            return null;
        }

        if ($zipCode->getZipRangeStart() === $zipCode->getZipRangeEnd() ||
            $zipCode->getZipRangeStart() && !$zipCode->getZipRangeEnd() ||
            !$zipCode->getZipRangeStart() && $zipCode->getZipRangeEnd()
        ) {
            $zipCode
                ->setZipCode($zipCode->getZipRangeStart() ?: $zipCode->getZipRangeEnd())
                ->setZipRangeStart(null)
                ->setZipRangeEnd(null);
        }

        return $zipCode;
    }
}
