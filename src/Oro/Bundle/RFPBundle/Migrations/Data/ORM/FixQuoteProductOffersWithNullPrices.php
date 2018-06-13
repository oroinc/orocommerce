<?php

namespace Oro\Bridge\RFPBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Set value (price) to 0 for QuoteProductOffers with NULL price.
 */
class FixQuoteProductOffersWithNullPrices extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->container->hasParameter('installed') || !$this->container->getParameter('installed')) {
            return;
        }

        $update = sprintf('UPDATE oro_sale_quote_prod_offer SET value = 0 WHERE value IS NULL');
        $manager->getConnection()->exec($update);
    }
}
