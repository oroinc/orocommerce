<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository;

class RegionMatcher extends AbstractMatcher
{
    /**
     * {@inheritdoc}
     */
    public function match(AbstractAddress $address)
    {
        /** @var TaxRuleRepository $taxRuleRepository */
        $taxRuleRepository = $this->doctrineHelper->getEntityRepositoryForClass($this->taxRuleClass);

        return $taxRuleRepository->findByCountryAndRegion(
            $address->getCountry(),
            $address->getRegion(),
            $address->getRegionText()
        );
    }
}
