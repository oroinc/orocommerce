<?php

namespace Oro\Bundle\TaxBundle\Migrations;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TaxBundle\Entity\AccountTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Entity\ZipCode;

class TaxEntitiesFactory
{
    /**
     * @param string $code
     * @param string $description
     * @param ObjectManager $persistTo (optional)
     * @param AbstractFixture $addReferenceTo (optional)
     *
     * @return AccountTaxCode
     */
    public function createAccountTaxCode(
        $code,
        $description,
        ObjectManager $persistTo = null,
        AbstractFixture $addReferenceTo = null
    ) {
        $taxCode = new AccountTaxCode();
        $taxCode->setCode($code);
        $taxCode->setDescription($description);

        if ($persistTo) {
            $persistTo->persist($taxCode);
        }

        if ($addReferenceTo) {
            $addReferenceTo->addReference($code, $taxCode);
        }

        return $taxCode;
    }

    /**
     * @param string $code
     * @param string $description
     * @param ObjectManager $persistTo (optional)
     * @param AbstractFixture $addReferenceTo (optional)
     *
     * @return ProductTaxCode
     */
    public function createProductTaxCode(
        $code,
        $description,
        ObjectManager $persistTo = null,
        AbstractFixture $addReferenceTo = null
    ) {
        $taxCode = new ProductTaxCode();
        $taxCode->setCode($code);
        $taxCode->setDescription($description);

        if ($persistTo) {
            $persistTo->persist($taxCode);
        }

        if ($addReferenceTo) {
            $addReferenceTo->addReference($code, $taxCode);
        }

        return $taxCode;
    }

    /**
     * @param float $rate
     * @param string $code
     * @param string $description
     * @param ObjectManager $persistTo (optional)
     * @param AbstractFixture $addReferenceTo (optional)
     *
     * @return Tax
     */
    public function createTax(
        $code,
        $rate,
        $description,
        ObjectManager $persistTo = null,
        AbstractFixture $addReferenceTo = null
    ) {
        $tax = new Tax();
        $tax->setCode($code);
        $tax->setRate($rate);
        $tax->setDescription($description);

        if ($persistTo) {
            $persistTo->persist($tax);
        }

        if ($addReferenceTo) {
            $addReferenceTo->addReference($code, $tax);
        }

        return $tax;
    }

    /**
     * @param string $code
     * @param string $description
     * @param Country $country
     * @param Region $region
     * @param array $zipCodes
     * @param ObjectManager $persistTo (optional)
     * @param AbstractFixture $addReferenceTo (optional)
     *
     * @return TaxJurisdiction
     */
    public function createTaxJurisdiction(
        $code,
        $description,
        Country $country,
        Region $region,
        $zipCodes,
        ObjectManager $persistTo = null,
        AbstractFixture $addReferenceTo = null
    ) {
        $jurisdiction = new TaxJurisdiction();
        $jurisdiction->setCode($code);
        $jurisdiction->setDescription($description);
        if ($country) {
            $jurisdiction->setCountry($country);
            if ($region) {
                $jurisdiction->setRegion($region);
            }
        }

        foreach ($zipCodes as $data) {
            $zipCode = new ZipCode();
            if (is_array($data)) {
                $zipCode->setZipRangeStart($data['start']);
                $zipCode->setZipRangeEnd($data['end']);
            } else {
                $zipCode->setZipCode($data);
            }

            $jurisdiction->addZipCode($zipCode);
        }

        if ($persistTo) {
            $persistTo->persist($jurisdiction);
        }

        if ($addReferenceTo) {
            $addReferenceTo->addReference($code, $jurisdiction);
        }

        return $jurisdiction;
    }

    /**
     * @param AccountTaxCode $accountTaxCode
     * @param ProductTaxCode $productTaxCode
     * @param TaxJurisdiction $taxJurisdiction
     * @param Tax $tax
     * @param string $description (optional)
     * @param ObjectManager $persistTo (optional)
     * @param AbstractFixture $addReferenceTo (optional)
     *
     * @return TaxRule
     *
     */
    public function createTaxRule(
        AccountTaxCode $accountTaxCode,
        ProductTaxCode $productTaxCode,
        TaxJurisdiction $taxJurisdiction,
        Tax $tax,
        $description = '',
        ObjectManager $persistTo = null,
        AbstractFixture $addReferenceTo = null
    ) {
        $taxRule = new TaxRule();
        $taxRule->setAccountTaxCode($accountTaxCode);
        $taxRule->setProductTaxCode($productTaxCode);
        $taxRule->setTaxJurisdiction($taxJurisdiction);
        $taxRule->setTax($tax);
        $taxRule->setDescription($description);

        if ($persistTo) {
            $persistTo->persist($taxRule);
        }

        if ($addReferenceTo) {
            $code = 'TAX_RULE-' . implode('-', [
                    $accountTaxCode->getCode(),
                    $productTaxCode->getCode(),
                    $taxJurisdiction->getCode(),
                    $tax->getCode()
                ]);
            $addReferenceTo->addReference($code, $taxRule);
        }

        return $taxRule;
    }
}
