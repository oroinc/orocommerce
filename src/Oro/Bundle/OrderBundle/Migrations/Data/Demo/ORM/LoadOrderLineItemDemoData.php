<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadProductPriceDemoData;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads demo data for order line items.
 */
class LoadOrderLineItemDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    private ContainerInterface $container;
    private ProductPriceProviderInterface $productPriceProvider;
    private ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory;
    private CombinedPriceListTreeHandler $priceListTreeHandler;

    /** @var array|Order[] */
    private array $orders = [];

    /** @var array|Product[] */
    private array $products = [];

    /** @var array|Price[] */
    private array $prices = [];

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;

        $this->productPriceProvider = $container->get('oro_pricing.provider.product_price');
        $this->productPriceCriteriaFactory = $container->get('oro_pricing.product_price_criteria_factory');
        $this->priceListTreeHandler = $container->get('oro_pricing.model.price_list_tree_handler');
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadOrderDemoData::class,
            LoadProductPriceDemoData::class,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @param EntityManager $manager
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function load(ObjectManager $manager): void
    {
        $this->disableLifecycleCallbacks($manager);
        $this->toggleFeatures(false);

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroOrderBundle/Migrations/Data/Demo/ORM/data/order-line-items.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        $priceTypes = [PriceTypeAwareInterface::PRICE_TYPE_UNIT, PriceTypeAwareInterface::PRICE_TYPE_BUNDLED];

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $orderLineItem = new OrderLineItem();

            $order = $this->getOrder($manager, $row['orderIdentifier']);

            $product = $this->getProduct($manager, $row['productSku']);
            $productUnit = $this->getProductUnit($manager, $row['productUnitCode']);
            $quantity = empty($row['freeFormProduct']) ? mt_rand(1, 50) : 1;

            $priceList = $this->priceListTreeHandler->getPriceList($order->getCustomer(), $order->getWebsite());
            $price = $priceList
                ? $this->getPrice($product, $productUnit, $quantity, $order->getCurrency(), $priceList, $order)
                : Price::create(mt_rand(10, 1000), $order->getCurrency());
            $date = empty($row['shipBy']) ? null : new \DateTime($row['shipBy']);

            $orderLineItem
                ->setFromExternalSource(mt_rand(0, 1))
                ->setProduct($product)
                ->setProductName($product->getName())
                ->setFreeFormProduct(null)
                ->setProductUnit($productUnit)
                ->setQuantity($quantity)
                ->setPriceType($priceTypes[array_rand($priceTypes)])
                ->setPrice($price)
                ->setShipBy($date)
                ->setComment($row['comment']);

            $order->addLineItem($orderLineItem);

            $manager->persist($orderLineItem);
            $manager->persist($order);
        }

        fclose($handler);

        $totalHandler = $this->container->get('oro_order.order.total.total_helper');
        $taxValueManager = $this->container->get('oro_tax.manager.tax_value_manager');
        foreach ($this->orders as $order) {
            if (!$order->getLineItems()->count()) {
                continue;
            }
            $totalHandler->fill($order);
        }

        $manager->flush();
        $manager->clear();

        $taxValueManager->clear();

        $this->toggleFeatures(true);
        $this->enableLifecycleCallbacks($manager);

        $this->products = [];
        $this->orders = [];
        $this->prices = [];
    }

    private function getOrder(EntityManagerInterface $manager, string $identifier): ?Order
    {
        if (!array_key_exists($identifier, $this->orders)) {
            $this->orders[$identifier] = $manager->getRepository(Order::class)
                ->findOneBy(['identifier' => $identifier]);
        }

        return $this->orders[$identifier];
    }

    private function getProduct(EntityManagerInterface $manager, string $sku): Product
    {
        if (!array_key_exists($sku, $this->products)) {
            $this->products[$sku] = $manager->getRepository(Product::class)->findOneBy(['sku' => $sku]);
        }

        return $this->products[$sku];
    }

    private function getProductUnit(EntityManagerInterface $manager, string $code): ?ProductUnit
    {
        return $manager->getReference(ProductUnit::class, $code);
    }

    private function getPrice(
        Product $product,
        ProductUnit $productUnit,
        int $quantity,
        string $currency,
        BasePriceList $priceList,
        Order $order
    ): Price {
        $productPriceCriteria = $this->productPriceCriteriaFactory
            ->create($product, $productUnit, $quantity, $currency);
        $identifier = $productPriceCriteria->getIdentifier();

        $priceListId = $priceList->getId();
        if (!isset($this->prices[$priceListId][$identifier])) {
            $searchScope = $this->getSearchScope($order);
            $prices = $this->productPriceProvider->getMatchedPrices([$productPriceCriteria], $searchScope);
            $this->prices[$priceListId][$identifier] = $prices[$identifier];
        }

        $price = $this->prices[$priceListId][$identifier];

        return $price ?: Price::create(mt_rand(10, 1000), $currency);
    }

    private function getSearchScope(Order $order): ProductPriceScopeCriteriaInterface
    {
        return $this->container->get('oro_pricing.model.product_price_scope_criteria_factory')
            ->createByContext($order);
    }

    private function enableLifecycleCallbacks(ObjectManager $manager): void
    {
        $orderMetadata = $this->getClassMetadata($manager, Order::class);

        $lifecycleCallbacks = $orderMetadata->lifecycleCallbacks;
        array_unshift($lifecycleCallbacks['prePersist'], 'updateTotalDiscounts');
        array_unshift($lifecycleCallbacks['prePersist'], 'prePersist');

        array_push($lifecycleCallbacks['preUpdate'], 'updateTotalDiscounts');

        $orderMetadata->setLifecycleCallbacks($lifecycleCallbacks);
    }

    private function disableLifecycleCallbacks(ObjectManager $manager): void
    {
        $orderMetadata = $this->getClassMetadata($manager, Order::class);
        $lifecycleCallbacks = $orderMetadata->lifecycleCallbacks;
        $lifecycleCallbacks['prePersist'] = array_diff(
            $lifecycleCallbacks['prePersist'],
            [
                'prePersist',
                'updateTotalDiscounts'
            ]
        );
        $lifecycleCallbacks['preUpdate'] = array_diff(
            $lifecycleCallbacks['preUpdate'],
            [
                'updateTotalDiscounts'
            ]
        );


        $orderMetadata->setLifecycleCallbacks($lifecycleCallbacks);
    }

    private function getClassMetadata(ObjectManager $manager, string $className): ClassMetadata
    {
        if (!isset($this->metadata[$className])) {
            $this->metadata[$className] = $manager->getClassMetadata($className);
        }

        return $this->metadata[$className];
    }

    private function toggleFeatures(?bool $enable): void
    {
        $configManager = $this->container->get('oro_config.global');
        $configManager->set('oro_promotion.feature_enabled', $enable ?? false);
        $configManager->flush();
    }
}
