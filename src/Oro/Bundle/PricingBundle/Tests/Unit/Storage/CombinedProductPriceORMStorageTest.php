<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Storage;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Storage\CombinedProductPriceORMStorage;
use Oro\Bundle\PricingBundle\Tests\Unit\Entity\Repository\Stub\CombinedProductPriceRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

class CombinedProductPriceORMStorageTest extends \PHPUnit\Framework\TestCase
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
     * @var PriceListTreeHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $priceListTreeHandler;

    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $featureChecker;

    /**
     * @var CombinedProductPriceORMStorage
     */
    protected $storage;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->priceListTreeHandler = $this->createMock(PriceListTreeHandler::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->storage = new CombinedProductPriceORMStorage(
            $this->registry,
            $this->shardManager,
            $this->priceListTreeHandler
        );
        $this->storage->setFeatureChecker($this->featureChecker);
        $this->storage->addFeature(self::FEATURE);
    }

    public function testGetPrices()
    {
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $priceList = $this->getEntity(CombinedPriceList::class, ['id' => 3]);
        $product = $this->getEntity(Product::class, ['id' => 4]);
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

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE)
            ->willReturn(true);

        $this->assertSame([$product], $this->storage->getPrices($scopeCriteria, [$product]));
    }

    public function testGetPricesFeatureDisabled()
    {
        $product = $this->getEntity(Product::class, ['id' => 4]);

        $this->priceListTreeHandler->expects($this->never())
            ->method('getPriceList');

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE)
            ->willReturn(false);

        /** @var ProductPriceScopeCriteriaInterface|\PHPUnit\Framework\MockObject\MockObject $scopeCriteria */
        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->assertSame([], $this->storage->getPrices($scopeCriteria, [$product]));
    }

    public function testGetPricesEmptyPriceList()
    {
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $product = $this->getEntity(Product::class, ['id' => 4]);
        $scopeCriteria = $this->getScopeCriteria($website, $customer);

        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE)
            ->willReturn(true);

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
        $priceList = $this->getEntity(CombinedPriceList::class, ['id' => 3, 'currencies' => ['USD']]);
        $scopeCriteria = $this->getScopeCriteria($website, $customer);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE)
            ->willReturn(true);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($customer, $website)
            ->willReturn($priceList);

        $this->assertSame(['USD'], $this->storage->getSupportedCurrencies($scopeCriteria));
    }

    public function testGetSupportedCurrenciesFeatureDisabled()
    {
        /** @var ProductPriceScopeCriteriaInterface|\PHPUnit\Framework\MockObject\MockObject $scopeCriteria */
        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE)
            ->willReturn(false);

        $this->assertSame([], $this->storage->getSupportedCurrencies($scopeCriteria));
    }

    public function testGetSupportedCurrenciesEmptyPriceList()
    {
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $scopeCriteria = $this->getScopeCriteria($website, $customer);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE)
            ->willReturn(true);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($customer, $website)
            ->willReturn(null);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE)
            ->willReturn(true);

        $this->assertSame([], $this->storage->getSupportedCurrencies($scopeCriteria));
    }

    /**
     * @param Website $website
     * @param Customer $customer
     * @return ProductPriceScopeCriteriaInterface
     */
    protected function getScopeCriteria(Website $website, Customer $customer): ProductPriceScopeCriteriaInterface
    {
        $scopeCriteria = new ProductPriceScopeCriteria();
        $scopeCriteria->setWebsite($website);
        $scopeCriteria->setCustomer($customer);

        return $scopeCriteria;
    }
}
