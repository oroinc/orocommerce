<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;

/**
 * Checks if a price list is referenced by price rule lexemes.
 *
 * Determines whether a price list is used in price rule expressions,
 * preventing deletion of price lists that are actively referenced in rules.
 */
class PriceListReferenceChecker
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var array|null
     */
    protected $references = null;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param PriceList $object
     * @return bool
     */
    public function isReferential(PriceList $object)
    {
        if ($this->references === null) {
            $this->references = $this->getRepository()->getRelationIds();
        }

        return in_array($object->getId(), $this->references, true);
    }

    /**
     * @return PriceRuleLexemeRepository
     */
    protected function getRepository()
    {
        return $this->registry->getManagerForClass(PriceRuleLexeme::class)->getRepository(PriceRuleLexeme::class);
    }
}
