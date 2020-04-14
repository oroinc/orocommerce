<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\TaxBundle\Cache\TaxCodesCache;
use Oro\Bundle\TaxBundle\Entity\Repository\AbstractTaxCodeRepository;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Provider\TaxCodeProvider;
use Oro\Component\Testing\Unit\EntityTrait;

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
     * @var TaxCodesCache|\PHPUnit\Framework\MockObject\MockObject
     */
    private $taxCodesCache;

    /**
     * @var TaxCodeProvider
     */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->productRepository = $this->getMockBuilder(AbstractTaxCodeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerRepository = $this->getMockBuilder(AbstractTaxCodeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->taxCodesCache = $this->getMockBuilder(TaxCodesCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new TaxCodeProvider(
            $this->productRepository,
            $this->customerRepository,
            $this->taxCodesCache
        );
    }

    public function testGetTaxCodeWhenTypeIsNotSupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown type: unsupportedType');

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

        $this->taxCodesCache
            ->expects($this->once())
            ->method('containsTaxCode')
            ->with($taxableObject)
            ->willReturn(true);

        $this->taxCodesCache
            ->expects($this->once())
            ->method('fetchTaxCode')
            ->with($taxableObject)
            ->willReturn($taxCode);

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

        $this->taxCodesCache
            ->expects($this->once())
            ->method('containsTaxCode')
            ->with($taxableObject)
            ->willReturn(false);

        $this->taxCodesCache
            ->expects($this->once())
            ->method('fetchTaxCode')
            ->with($taxableObject)
            ->willReturn($taxCode);

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

        $this->taxCodesCache
            ->expects($this->once())
            ->method('containsTaxCode')
            ->with($taxableObject)
            ->willReturn(false);

        $this->taxCodesCache
            ->expects($this->once())
            ->method('fetchTaxCode')
            ->with($taxableObject)
            ->willReturn($taxCode);

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

        $this->taxCodesCache
            ->expects($this->exactly(2))
            ->method('saveTaxCode')
            ->withConsecutive([$object1, $taxCode1], [$object2, $taxCode2]);

        $this->provider->preloadTaxCodes($taxCodeType, [$object1, $object2]);
    }
}
