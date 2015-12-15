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
        if (null === $zipCodes) {
            return '';
        }

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
        if (!$value) {
            return new ArrayCollection();
        }

        $collection = new ArrayCollection();
        $codes = array_unique($this->prepareCodes(explode(self::RECORD_DELIMITER, $value)));

        foreach ($codes as $code) {
            $zipCode = new ZipCode();

            if (false !== strstr($code, self::RANGE_DELIMITER)) {
                $codeRange = $this->prepareCodes(explode(self::RANGE_DELIMITER, $code, 2));
                sort($codeRange, SORT_NUMERIC);
                list ($rangeStart, $rangeEnd) = array_pad($codeRange, 2, null);

                $zipCode->setZipRangeStart($rangeStart);
                $zipCode->setZipRangeEnd($rangeEnd);
            } else {
                $zipCode->setZipCode($code);
            }

            $collection->add($zipCode);
        }

        return $collection;
    }

    /**
     * @param array $codes
     * @return array
     */
    private function prepareCodes($codes)
    {
        return array_filter(array_map('trim', $codes));
    }
}
