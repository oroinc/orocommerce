<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Storage;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Storage\CombinedProductPriceORMStorage;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;

class CombinedProductPriceORMStorageTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var CombinedPriceListTreeHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceListTreeHandler;

    /** @var CombinedProductPriceORMStorage */
    private $storage;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->priceListTreeHandler = $this->createMock(CombinedPriceListTreeHandler::class);

        $this->storage = new CombinedProductPriceORMStorage(
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

    private function getCombinedPriceList(int $id): CombinedPriceList
    {
        $priceList = new CombinedPriceList();
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
        $priceList = $this->getCombinedPriceList(3);
        $product = $this->getProduct(4);
        $scopeCriteria = $this->getScopeCriteria($website, $customer);

        $repository = $this->createMock(CombinedProductPriceRepository::class);
        $repository->expects($this->once())
            ->method('getPricesBatch')
            ->with($this->shardManager, $priceList->getId(), [4], null, null)
            ->willReturn([$product]);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(CombinedProductPrice::class)
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CombinedProductPrice::class)
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
        $priceList = $this->getCombinedPriceList(3);
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
