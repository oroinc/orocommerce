<?php

namespace Oro\Bundle\FixedProductShippingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Load Shipping Cost product price attribute.
 */
class LoadPriceAttributePriceListData extends AbstractFixture implements ContainerAwareInterface
{
    public const SHIPPING_COST_NAME = 'Shipping Cost';
    public const SHIPPING_COST_FIELD = 'shippingCost';

    /**
     * @var ContainerInterface|null
     */
    protected ?ContainerInterface $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Organization $organization */
        $organization = $manager->getRepository(Organization::class)->getFirst();

        $currencies = $this->container->get('oro_currency.config.currency')->getCurrencyList();
        $priceAttribute = new PriceAttributePriceList();
        $priceAttribute->setName(self::SHIPPING_COST_NAME)
            ->setFieldName(self::SHIPPING_COST_FIELD)
            ->setCurrencies($currencies)
            ->setOrganization($organization);

        $manager->persist($priceAttribute);
        $manager->flush();
    }
}
