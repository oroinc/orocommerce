<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Behat;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;
use Oro\Bundle\AddressBundle\Entity\Repository\AddressTypeRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\ReferenceRepositoryInitializer as BaseInitializer;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionRepository;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AccountBundle\Entity\AccountUserRole;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCurrency;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class ReferenceRepositoryInitializer extends BaseInitializer
{
    public function init()
    {
        parent::init();
        /** @var EntityRepository $repository */
        $repository = $this->em->getRepository('OroAddressBundle:Country');
        /** @var Country $germany */
        $germany = $repository->findOneBy(['name' => 'Germany']);
        $this->referenceRepository->set('germany', $germany);

        /** @var RegionRepository $repository */
        $repository = $this->em->getRepository('OroAddressBundle:Region');
        /** @var Region $berlin */
        $berlin = $repository->findOneBy(['name' => 'Berlin']);
        $this->referenceRepository->set('berlin', $berlin);

        /** @var AccountUserRoleRepository $repository */
        $repository = $this->em->getRepository('OroAccountBundle:AccountUserRole');
        /** @var AccountUserRole buyer */
        $buyer = $repository->findOneBy(['role' => 'ROLE_FRONTEND_BUYER']);
        $this->referenceRepository->set('buyer', $buyer);

        /** @var ProductUnitRepository $repository */
        $repository = $this->em->getRepository('OroProductBundle:ProductUnit');
        /** @var ProductUnit item*/
        $item = $repository->findOneBy(['code' => 'item']);
        $this->referenceRepository->set('item', $item);

        /** @var AddressTypeRepository $repository */
        $repository = $this->em->getRepository('OroAddressBundle:AddressType');
        /** @var AddressType $billingType*/
        $billingType = $repository->findOneBy(['name' => 'billing']);
        $this->referenceRepository->set('billingType', $billingType);
        /** @var AddressType $shippingType*/
        $shippingType = $repository->findOneBy(['name' => 'shipping']);
        $this->referenceRepository->set('shippingType', $shippingType);

        /** @var EntityRepository $repository */
        $repository = $this->em->getRepository('OroPricingBundle:PriceListCurrency');
        /** @var PriceListCurrency EUR*/
        $eur = $repository->findOneBy(['currency' => 'EUR']);
        $this->referenceRepository->set('eur', $eur);

        /** @var PriceListRepository $repository */
        $repository = $this->em->getRepository('OroPricingBundle:PriceList');
        /** @var PriceList $pricelist1*/
        $pricelist1 = $repository->findOneBy(['id' => '1']);
        $this->referenceRepository->set('pricelist1', $pricelist1);

        /** @var WebsiteRepository $repository */
        $repository = $this->em->getRepository('OroWebsiteBundle:Website');
        /** @var Website $website1*/
        $website1 = $repository->findOneBy(['id' => '1']);
        $this->referenceRepository->set('website1', $website1);

        /** @var CombinedPriceListRepository $repository */
        $repository = $this->em->getRepository('OroPricingBundle:CombinedPriceList');
        /** @var CombinedPriceList $combinedPriceList*/
        $combinedPriceList = $repository->findOneBy(['id' => '1']);
        $this->referenceRepository->set('combinedPriceList', $combinedPriceList);

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        $enumInventoryStatuses = $this->em->getRepository($inventoryStatusClassName)->findOneBy(['id' => 'in_stock']);
        $this->referenceRepository->set('enumInventoryStatuses', $enumInventoryStatuses);
    }
}
