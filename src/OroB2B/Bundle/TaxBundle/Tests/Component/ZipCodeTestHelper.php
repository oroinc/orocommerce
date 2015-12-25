<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Component;

use OroB2B\Bundle\TaxBundle\Entity\ZipCode;

class ZipCodeTestHelper
{
    /**
     * @param string $code
     * @return ZipCode
     */
    public static function getSingleValueZipCode($code)
    {
        $zipCode = new ZipCode();
        $zipCode->setZipCode($code);

        return $zipCode;
    }

    /**
     * @param string $start
     * @param string $end
     * @return ZipCode
     */
    public static function getRangeZipCode($start, $end)
    {
        $zipCode = new ZipCode();
        $zipCode->setZipRangeStart($start);
        $zipCode->setZipRangeEnd($end);

        return $zipCode;
    }
}
