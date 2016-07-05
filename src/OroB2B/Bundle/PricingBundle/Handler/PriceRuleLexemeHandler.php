<?php

namespace OroB2B\Bundle\PricingBundle\Handler;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class PriceRuleLexemeHandler
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    protected function addLexemes(PriceList $priceList)
    {
        $assigmentRules=
        /** @var  $assigmentRule */
        $assigmentRule = $priceList->getName();
    }
}
