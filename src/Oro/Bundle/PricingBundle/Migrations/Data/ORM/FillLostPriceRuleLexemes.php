<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Fill price rule lexemes that were lost during price list duplication
 * for Price Lists with non-empty Product Assignment Rule.
 */
class FillLostPriceRuleLexemes extends AbstractFixture implements
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $lexemeHandler = $this->container->get('oro_pricing.handler.price_rule_lexeme_handler');
        $priceLists = $this->getPriceLists($manager);
        if (!$priceLists) {
            return;
        }

        foreach ($priceLists as $priceList) {
            $lexemeHandler->updateLexemes($priceList);
        }
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return array|PriceList[]
     */
    private function getPriceLists(ObjectManager $manager)
    {
        $subQb = $manager->getRepository(PriceRuleLexeme::class)->createQueryBuilder('l');
        $subQb->select('l.id')
            ->where($subQb->expr()->eq('l.priceList', 'pl.id'))
            ->andWhere($subQb->expr()->isNull('l.priceRule'));

        $qb = $manager->getRepository(PriceList::class)->createQueryBuilder('pl');
        $qb
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull('pl.productAssignmentRule'),
                    $qb->expr()->neq('pl.productAssignmentRule', ':emptyString')
                )
            )
            ->andWhere(
                $qb->expr()->not($qb->expr()->exists($subQb->getDQL()))
            )
            ->setParameter('emptyString', '', Types::STRING);

        return $qb->getQuery()->getResult();
    }
}
