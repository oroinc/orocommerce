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
use Oro\Component\Testing\Unit\EntityTrait;

class ProductPriceORMStorageTest extends \PHPUnit\Framework\TestCase
{
    const FEATURE = 'test_feature';

    use EntityTrait;

    /**
     * @var ShardManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shardManager;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var FlatPriceListTreeHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $priceListTreeHandler;

    /**
     * @var ProductPriceORMStorage
     */
    protected $storage;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->priceListTreeHandler = $this->createMock(FlatPriceListTreeHandler::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->storage = new ProductPriceORMStorage(
            $this->registry,
            $this->shardManager,
            $this->priceListTreeHandler
        );
    }

    public function testGetPrices()
    {
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $priceList = $this->getEntity(PriceList::class, ['id' => 3]);
        $product = $this->getEntity(Product::class, ['id' => 4]);
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
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $product = $this->getEntity(Product::class, ['id' => 4]);
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
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $priceList = $this->getEntity(PriceList::class, ['id' => 3, 'currencies' => ['USD']]);
        $scopeCriteria = $this->getScopeCriteria($website, $customer);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($customer, $website)
            ->willReturn($priceList);

        $this->assertSame(['USD'], $this->storage->getSupportedCurrencies($scopeCriteria));
    }

    public function testGetSupportedCurrenciesEmptyPriceList()
    {
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $scopeCriteria = $this->getScopeCriteria($website, $customer);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($customer, $website)
            ->willReturn(null);

        $this->assertSame([], $this->storage->getSupportedCurrencies($scopeCriteria));
    }

    protected function getScopeCriteria(Website $website, Customer $customer): ProductPriceScopeCriteriaInterface
    {
        $scopeCriteria = new ProductPriceScopeCriteria();
        $scopeCriteria->setWebsite($website);
        $scopeCriteria->setCustomer($customer);

        return $scopeCriteria;
    }
}
