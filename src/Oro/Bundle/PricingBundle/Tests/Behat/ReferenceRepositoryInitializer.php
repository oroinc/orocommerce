<?php

namespace Oro\Bundle\PricingBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCurrency;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, Collection $referenceRepository)
    {
        /** @var EntityRepository $repository */
        $repository = $doctrine->getManager()->getRepository('OroPricingBundle:PriceListCurrency');
        /** @var PriceListCurrency EUR*/
        $eur = $repository->findOneBy(['currency' => 'EUR']);
        $referenceRepository->set('eur', $eur);

        /** @var PriceListRepository $repository */
        $repository = $doctrine->getManager()->getRepository('OroPricingBundle:PriceList');
        /** @var PriceList $pricelist1*/
        $pricelist1 = $repository->findOneBy(['id' => '1']);
        $referenceRepository->set('defaultPriceList', $pricelist1);

        /** @var CombinedPriceListRepository $repository */
        $repository = $doctrine->getManager()->getRepository('OroPricingBundle:CombinedPriceList');
        /** @var CombinedPriceList $combinedPriceList*/
        $combinedPriceList = $repository->findOneBy(['id' => '1']);
        $referenceRepository->set('combinedPriceList', $combinedPriceList);
    }
}
