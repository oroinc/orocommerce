<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Cache\TaxCodesCache;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class TaxCodesCacheTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheProvider;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var TaxCodesCache
     */
    private $taxCodesCache;

    protected function setUp(): void
    {
        $this->cacheProvider = $this->createMock(CacheProvider::class);

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->taxCodesCache = new TaxCodesCache($this->cacheProvider, $this->doctrineHelper);
    }

    /**
     * @dataProvider containsDataProvider
     * @param bool $contains
     */
    public function testContainsTaxCode($contains)
    {
        $object = $this->getEntity(Customer::class, ['id' => 77]);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityIdentifier')
            ->with($object)
            ->willReturn([77]);

        $this->cacheProvider
            ->expects($this->once())
            ->method('contains')
            ->with('Oro\Bundle\CustomerBundle\Entity\Customer_77')
            ->willReturn($contains);

        $this->assertEquals($contains, $this->taxCodesCache->containsTaxCode($object));
    }

    /**
     * @return array
     */
    public function containsDataProvider()
    {
        return [
            'contains' => [true],
            'not contains' => [false],
        ];
    }

    public function testFetchTaxCode()
    {
        $object = $this->getEntity(Customer::class, ['id' => 77]);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityIdentifier')
            ->with($object)
            ->willReturn([77]);

        $cachedTaxCode = new TaxCode('TAX1', TaxCodeInterface::TYPE_ACCOUNT);
        $this->cacheProvider
            ->expects($this->once())
            ->method('fetch')
            ->with('Oro\Bundle\CustomerBundle\Entity\Customer_77')
            ->willReturn($cachedTaxCode);

        $this->assertEquals($cachedTaxCode, $this->taxCodesCache->fetchTaxCode($object));
    }

    public function testSaveTaxCode()
    {
        $object = $this->getEntity(Customer::class, ['id' => 77]);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityIdentifier')
            ->with($object)
            ->willReturn([77]);

        $taxCode = new TaxCode('TAX1', TaxCodeInterface::TYPE_ACCOUNT);
        $this->cacheProvider
            ->expects($this->once())
            ->method('save')
            ->with('Oro\Bundle\CustomerBundle\Entity\Customer_77', $taxCode)
            ->willReturn($taxCode);

        $this->assertEquals($taxCode, $this->taxCodesCache->saveTaxCode($object, $taxCode));
    }
}
