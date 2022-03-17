<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Entity\Repository\AbstractTaxCodeRepository;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Provider\TaxCodeProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class TaxCodeProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var AbstractTaxCodeRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productRepository;

    /**
     * @var AbstractTaxCodeRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerRepository;

    /**
     * @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $taxCodesCache;

    /**
     * @var TaxCodeProvider
     */
    private $provider;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(AbstractTaxCodeRepository::class);
        $this->customerRepository = $this->createMock(AbstractTaxCodeRepository::class);
        $this->taxCodesCache = $this->createMock(CacheInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->provider = new TaxCodeProvider(
            $this->productRepository,
            $this->customerRepository,
            $this->taxCodesCache,
            $this->doctrineHelper
        );
    }

    public function testGetTaxCodeWhenTypeIsNotSupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown type: unsupportedType');
        $this->assertCache();

        $this->provider->getTaxCode('unsupportedType', new \stdClass());
    }

    /**
     * @dataProvider taxCodeTypesDataProvider
     * @param string $taxCodeType
     */
    public function testGetTaxCodeWhenCacheExists($taxCodeType)
    {
        $taxableObject = new \stdClass();
        $taxCode = new TaxCode('TAX1', $taxCodeType);
        $this->assertCache($taxCode);

        $this->assertEquals($taxCode, $this->provider->getTaxCode($taxCodeType, $taxableObject));
    }

    /**
     * @return array
     */
    public function taxCodeTypesDataProvider()
    {
        return [
            'account type' => [TaxCodeInterface::TYPE_ACCOUNT],
            'account group type' => [TaxCodeInterface::TYPE_ACCOUNT_GROUP],
            'product type' => [TaxCodeInterface::TYPE_PRODUCT]
        ];
    }

    public function testGetTaxCodeWhenCacheNotExistsForProductTaxType()
    {
        $taxableObject = new \stdClass();
        $taxType = TaxCodeInterface::TYPE_PRODUCT;
        $taxCode = new TaxCode('TAX1', $taxType);

        $this->assertCache();
        $this->productRepository
            ->expects($this->once())
            ->method('findOneByEntity')
            ->with($taxType, $taxableObject)
            ->willReturn($taxCode);

        $this->assertEquals($taxCode, $this->provider->getTaxCode($taxType, $taxableObject));
    }

    /**
     * @dataProvider customerTaxCodeTypesDataProvider
     * @param string $taxType
     */
    public function testGetTaxCodeWhenCacheNotExistsForCustomerTaxTypes($taxType)
    {
        $taxableObject = new \stdClass();
        $taxCode = new TaxCode('TAX1', $taxType);

        $this->assertCache();
        $this->customerRepository
            ->expects($this->once())
            ->method('findOneByEntity')
            ->with($taxType, $taxableObject)
            ->willReturn($taxCode);

        $this->assertEquals($taxCode, $this->provider->getTaxCode($taxType, $taxableObject));
    }

    /**
     * @return array
     */
    public function customerTaxCodeTypesDataProvider()
    {
        return [
            'account tax code type' => [
                'type' => TaxCodeInterface::TYPE_ACCOUNT,
            ],
            'account group tax code type' => [
                'type' => TaxCodeInterface::TYPE_ACCOUNT_GROUP,
            ]
        ];
    }

    public function testPreloadTaxCodes()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objects = [$object1, $object2];
        $taxCodeType = TaxCodeInterface::TYPE_PRODUCT;

        $taxCode1 = $this->createMock(TaxCodeInterface::class);
        $taxCode2 = $this->createMock(TaxCodeInterface::class);

        $this->productRepository
            ->expects($this->once())
            ->method('findManyByEntities')
            ->with($taxCodeType, $objects)
            ->willReturn([$taxCode1, $taxCode2]);

        $this->doctrineHelper
            ->expects($this->exactly(2))
            ->method('getEntityIdentifier')
            ->willReturnOnConsecutiveCalls([1], [2]);
        $this->taxCodesCache
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['stdClass_1'], ['stdClass_2'])
            ->willReturnOnConsecutiveCalls($taxCode1, $taxCode2);

        $this->provider->preloadTaxCodes($taxCodeType, [$object1, $object2]);
    }

    private function assertCache($isCached = false): void
    {
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityIdentifier')
            ->willReturn([77]);
        if ($isCached) {
            $this->taxCodesCache
                ->expects($this->any())
                ->method('get')
                ->willReturn($isCached);
        } else {
            $this->taxCodesCache
                ->expects($this->any())
                ->method('get')
                ->willReturnCallback(function ($cacheKey, $callback) {
                    $item = $this->createMock(ItemInterface::class);
                    return $callback($item);
                });
        }
    }
}
