<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository;

class CountryMatcher implements MatcherInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $taxRuleClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function match(AbstractAddress $address)
    {
        /** @var TaxRuleRepository $taxRuleRepository */
        $taxRuleRepository = $this->doctrineHelper->getEntityRepositoryForClass($this->taxRuleClass);

        return $taxRuleRepository->findByCountry($address->getCountry());
    }

    /**
     * @param string $taxRuleClass
     */
    public function setTaxRuleClass($taxRuleClass)
    {
        $this->taxRuleClass = $taxRuleClass;
    }
}
