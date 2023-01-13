<?php

namespace Oro\Bundle\PricingBundle\Tests\Behat;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCurrency;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        $repository = $doctrine->getManager()->getRepository(PriceListCurrency::class);
        /** @var PriceListCurrency $eur */
        $eur = $repository->findOneBy(['currency' => 'EUR']);
        if ($eur) {
            $referenceRepository->set('eur', $eur);
        }

        /** @var PriceListRepository $repository */
        $repository = $doctrine->getManager()->getRepository(PriceList::class);
        /** @var PriceList $pricelist1 */
        $pricelist1 = $repository->findOneBy(['id' => '1']);
        $referenceRepository->set('defaultPriceList', $pricelist1);
    }
}
