<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;

class PriceListReferenceChecker
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $references = null;

    /**
     * @param ManagerRegistry $registry
     */
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

        return in_array($object->getId(), $this->references);
    }

    /**
     * @return PriceRuleLexemeRepository
     */
    protected function getRepository()
    {
        return $this->registry->getManagerForClass(PriceRuleLexeme::class)->getRepository(PriceRuleLexeme::class);
    }
}
