<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Storage;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\FlatPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Storage\ProductPriceORMStorage;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;

class ProductPriceORMStorageTest extends \PHPUnit\Framework\TestCase
{
    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry;

    private ShardManager|\PHPUnit\Framework\MockObject\MockObject  $shardManager;

    private FlatPriceListTreeHandler|\PHPUnit\Framework\MockObject\MockObject $priceListTreeHandler;

    private ProductPriceORMStorage $storage;

    private FeatureChecker|FlatPriceListTreeHandler|\PHPUnit\Framework\MockObject\MockObject $featureChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->priceListTreeHandler = $this->createMock(FlatPriceListTreeHandler::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->storage = new ProductPriceORMStorage(
            $this->registry,
            $this->shardManager,
            $this->priceListTreeHandler
        );
    }

    private function getCustomer(int $id): Customer
    {
        $customer = new Customer();
        ReflectionUtil::setId($customer, $id);

        return $customer;
    }

    private function getWebsite(int $id): Website
    {
        $website = new Website();
        ReflectionUtil::setId($website, $id);

        return $website;
    }

    private function getPriceList(int $id): PriceList
    {
        $priceList = new PriceList();
        ReflectionUtil::setId($priceList, $id);

        return $priceList;
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    private function getScopeCriteria(Website $website, Customer $customer): ProductPriceScopeCriteriaInterface
    {
        $scopeCriteria = new ProductPriceScopeCriteria();
        $scopeCriteria->setWebsite($website);
        $scopeCriteria->setCustomer($customer);

        return $scopeCriteria;
    }

    public function testGetPrices()
    {
        $customer = $this->getCustomer(1);
        $website = $this->getWebsite(2);
        $priceList = $this->getPriceList(3);
        $product = $this->getProduct(4);
        $scopeCriteria = $this->getScopeCriteria($website, $customer);

        $repository = $this->createMock(ProductPriceRepository::class);
        $repository->expects($this->once())
            ->method('getPricesBatch')
            ->with($this->shardManager, $priceList->getId(), [4], null, null)
            ->willReturn([$product]);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(ProductPrice::class)
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ProductPrice::class)
            ->willReturn($manager);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($customer, $website)
            ->willReturn($priceList);

        $this->assertSame([$product], $this->storage->getPrices($scopeCriteria, [$product]));
    }

    public function testGetPricesEmptyPriceList()
    {
        $customer = $this->getCustomer(1);
        $website = $this->getWebsite(2);
        $product = $this->getProduct(4);
        $scopeCriteria = $this->getScopeCriteria($website, $customer);

        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($customer, $website)
            ->willReturn(null);

        $this->assertSame([], $this->storage->getPrices($scopeCriteria, [$product]));
    }

    public function testGetSupportedCurrencies()
    {
        $customer = $this->getCustomer(1);
        $website = $this->getWebsite(2);
        $priceList = $this->getPriceList(3);
        $priceList->setCurrencies(['USD']);
        $scopeCriteria = $this->getScopeCriteria($website, $customer);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($customer, $website)
            ->willReturn($priceList);

        $this->assertSame(['USD'], $this->storage->getSupportedCurrencies($scopeCriteria));
    }

    public function testGetSupportedCurrenciesEmptyPriceList()
    {
        $customer = $this->getCustomer(1);
        $website = $this->getWebsite(2);
        $scopeCriteria = $this->getScopeCriteria($website, $customer);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($customer, $website)
            ->willReturn(null);

        $this->assertSame([], $this->storage->getSupportedCurrencies($scopeCriteria));
    }
}
