<?php

namespace Oro\Bundle\TaxBundle\Migrations;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Entity\ZipCode;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

/**
 * Factory for creating taxes
 */
class TaxEntitiesFactory
{
    use UserUtilityTrait;

    /**
     * @param string $code
     * @param string $description
     * @param ObjectManager $manager
     * @param AbstractFixture $addReferenceTo (optional)
     *
     * @return CustomerTaxCode
     */
    public function createCustomerTaxCode(
        $code,
        $description,
        ObjectManager $manager,
        AbstractFixture $addReferenceTo = null
    ) {
        $owner = $this->getFirstUser($manager);

        $taxCode = new CustomerTaxCode();
        $taxCode->setCode($code);
        $taxCode->setDescription($description);
        $taxCode->setOwner($owner);
        $taxCode->setOrganization($owner->getOrganization());

        $manager->persist($taxCode);

        if ($addReferenceTo) {
            $addReferenceTo->addReference($code, $taxCode);
        }

        return $taxCode;
    }

    /**
     * @param string $code
     * @param string $description
     * @param OrganizationInterface $organization
     * @param ObjectManager $persistTo (optional)
     * @param AbstractFixture $addReferenceTo (optional)
     *
     * @return ProductTaxCode
     */
    public function createProductTaxCode(
        string $code,
        string $description,
        OrganizationInterface $organization,
        ObjectManager $persistTo = null,
        AbstractFixture $addReferenceTo = null
    ) {
        $taxCode = new ProductTaxCode();
        $taxCode->setCode($code);
        $taxCode->setDescription($description);
        $taxCode->setOrganization($organization);

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
     * @param CustomerTaxCode $customerTaxCode
     * @param ProductTaxCode $productTaxCode
     * @param TaxJurisdiction $taxJurisdiction
     * @param Tax $tax
     * @param string $description (optional)
     * @param ObjectManager $persistTo (optional)
     * @param AbstractFixture $addReferenceTo (optional)
     *
     * @return TaxRule
     */
    public function createTaxRule(
        CustomerTaxCode $customerTaxCode,
        ProductTaxCode $productTaxCode,
        TaxJurisdiction $taxJurisdiction,
        Tax $tax,
        $description = '',
        ObjectManager $persistTo = null,
        AbstractFixture $addReferenceTo = null
    ) {
        $taxRule = new TaxRule();
        $taxRule->setCustomerTaxCode($customerTaxCode);
        $taxRule->setProductTaxCode($productTaxCode);
        $taxRule->setTaxJurisdiction($taxJurisdiction);
        $taxRule->setTax($tax);
        $taxRule->setDescription($description);

        if ($persistTo) {
            $persistTo->persist($taxRule);
        }

        if ($addReferenceTo) {
            $code = 'TAX_RULE-' . implode('-', [
                    $customerTaxCode->getCode(),
                    $productTaxCode->getCode(),
                    $taxJurisdiction->getCode(),
                    $tax->getCode()
                ]);
            $addReferenceTo->addReference($code, $taxRule);
        }

        return $taxRule;
    }
}
