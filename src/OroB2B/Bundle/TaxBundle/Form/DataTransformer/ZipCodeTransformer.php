<?php

namespace OroB2B\Bundle\TaxBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use OroB2B\Bundle\TaxBundle\Entity\ZipCode;

class ZipCodeTransformer implements DataTransformerInterface
{
    const RANGE_DELIMITER = '-';
    const RECORD_DELIMITER = ',';

    /**
     * {@inheritdoc}
     * @param Collection|ZipCode[] $zipCodes
     */
    public function transform($zipCodes)
    {
        $transformedCodes = [];
        foreach ($zipCodes as $code) {
            if ($code->isSingleZipCode()) {
                $transformedCodes[] = $code->getZipCode();
            } else {
                $transformedCodes[] = $code->getZipRangeStart() . self::RANGE_DELIMITER . $code->getZipRangeEnd();
            }
        }

        return implode(self::RECORD_DELIMITER, $transformedCodes);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $collection = new ArrayCollection();
        $codes = explode(self::RECORD_DELIMITER, $value);

        foreach ($codes as $code) {
            $code = trim($code);
            $zipCode = new ZipCode();

            // TODO: Validation required
            if (false !== strstr($code, self::RANGE_DELIMITER)) {
                $codeRange = explode(self::RANGE_DELIMITER, $code, 2);
                array_map('trim', $codeRange);
                sort($codeRange, SORT_NUMERIC);

                $zipCode->setZipRangeStart($codeRange[0]);
                $zipCode->setZipRangeEnd($codeRange[1]);
            } else {
                $zipCode->setZipCode($code);
            }

            $collection->add($zipCode);
        }

        return $collection;
    }
}
