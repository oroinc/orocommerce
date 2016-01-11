<?php

namespace OroB2B\Bundle\TaxBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\AddressBundle\Entity\Country;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;

class TaxRuleRepository extends EntityRepository
{
    /**
     * Find TaxRules by Country
     *
     * @param Country $country
     * @return TaxRule[]
     */
    public function findByCountry(Country $country)
    {
        return $this->findBy(['taxJurisdiction.country' => $country]);
    }
}
