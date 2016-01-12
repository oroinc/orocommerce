<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository;

class ZipCodeMatcher extends AbstractMatcher
{
    /**
     * {@inheritdoc}
     */
    public function match(AbstractAddress $address)
    {
        /** @var TaxRuleRepository $taxRuleRepository */
        $taxRuleRepository = $this->doctrineHelper->getEntityRepository($this->taxRuleClass);

        $regionText = $address->getRegion() ? null : $address->getRegionText();
        $country = $address->getRegion() ? null : $address->getCountry();

        return $taxRuleRepository->findByZipCode(
            $address->getPostalCode(),
            $address->getRegion(),
            $regionText,
            $country
        );
    }
}
