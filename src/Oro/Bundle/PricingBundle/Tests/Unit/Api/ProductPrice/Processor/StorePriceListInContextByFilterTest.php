<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PricingBundle\Api\ProductPrice\PriceListIDContextStorageInterface;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\StorePriceListInContextByFilter;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use PHPUnit\Framework\TestCase;

class StorePriceListInContextByFilterTest extends TestCase
{
    const PRICE_LIST_ID = 21;

    /**
     * @var PriceListIDContextStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceListIDContextStorage;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    /**
     * @var StorePriceListInContextByFilter
     */
    private $processor;

    protected function setUp()
    {
        $this->priceListIDContextStorage = $this->createMock(PriceListIDContextStorageInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->context = $this->createMock(Context::class);

        $this->processor = new StorePriceListInContextByFilter(
            $this->priceListIDContextStorage,
            $this->doctrineHelper
        );
    }

    public function testProcessWrongType()
    {
        $this->priceListIDContextStorage
            ->expects(static::never())
            ->method('store');

        $this->processor->process($this->createMock(ApiContext::class));
    }

    public function testProcessNoPriceList()
    {
        $filterValues = $this->createMock(FilterValueAccessorInterface::class);
        $filterValues
            ->expects(static::once())
            ->method('has')
            ->willReturn(false);

        $this->context
            ->expects(static::once())
            ->method('getFilterValues')
            ->willReturn($filterValues);

        $this->context
            ->expects(static::once())
            ->method('addError')
            ->with(
                Error::createValidationError(
                    Constraint::FILTER,
                    'priceList filter is required'
                )
            );

        $this->processor->process($this->context);
    }

    public function testProcessWrongPriceList()
    {
        $filterValues = $this->createFilterValuesMock();

        $this->context
            ->expects(static::exactly(2))
            ->method('getFilterValues')
            ->willReturn($filterValues);
        $this->context
            ->expects(static::once())
            ->method('addError')
            ->with(
                Error::createValidationError(
                    Constraint::FILTER,
                    'specified priceList does not exist'
                )
            );

        $this->doctrineHelper
            ->expects(static::once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($this->createMock(EntityRepository::class));

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $filterValues = $this->createFilterValuesMock();

        $this->context
            ->expects(static::exactly(2))
            ->method('getFilterValues')
            ->willReturn($filterValues);
        $this->context
            ->expects(static::never())
            ->method('addError');

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(static::once())
            ->method('find')
            ->willReturn(new PriceList());

        $this->doctrineHelper
            ->expects(static::once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($repository);

        $this->priceListIDContextStorage
            ->expects(static::once())
            ->method('store')
            ->with(self::PRICE_LIST_ID, $this->context);

        $this->processor->process($this->context);
    }

    /**
     * @return FilterValueAccessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createFilterValuesMock()
    {
        $filterValue = $this->createMock(FilterValue::class);
        $filterValue
            ->expects(static::once())
            ->method('getValue')
            ->willReturn(self::PRICE_LIST_ID);

        $filterValues = $this->createMock(FilterValueAccessorInterface::class);
        $filterValues
            ->expects(static::once())
            ->method('has')
            ->willReturn(true);
        $filterValues
            ->expects(static::once())
            ->method('get')
            ->willReturn($filterValue);

        return $filterValues;
    }
}
