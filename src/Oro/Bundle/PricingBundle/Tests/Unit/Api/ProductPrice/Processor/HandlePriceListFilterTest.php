<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Filter\TestFilterValueAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\HandlePriceListFilter;
use Oro\Bundle\PricingBundle\Entity\PriceList;

class HandlePriceListFilterTest extends GetListProcessorTestCase
{
    private const PRICE_LIST_ID = 21;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var HandlePriceListFilter */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new HandlePriceListFilter($this->doctrineHelper);
    }

    public function testProcessNoPriceList()
    {
        $this->context->setFilterValues(new TestFilterValueAccessor());
        $this->processor->process($this->context);
        self::assertEquals(
            [Error::createValidationError(Constraint::FILTER, 'The "priceList" filter is required.')],
            $this->context->getErrors()
        );
    }

    public function testProcessWrongPriceList()
    {
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($this->createMock(EntityRepository::class));

        $filterValues = new TestFilterValueAccessor();
        $filterValues->set('priceList', new FilterValue('priceList', self::PRICE_LIST_ID));
        $this->context->setFilterValues($filterValues);
        $this->processor->process($this->context);
        self::assertEquals(
            [Error::createValidationError(Constraint::FILTER, 'The specified price list does not exist.')],
            $this->context->getErrors()
        );
    }

    public function testProcess()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->willReturn(new PriceList());

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($repository);

        $filterValues = new TestFilterValueAccessor();
        $filterValues->set('priceList', new FilterValue('priceList', self::PRICE_LIST_ID));
        $this->context->setFilterValues($filterValues);
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasErrors());
        self::assertSame(self::PRICE_LIST_ID, $this->context->get('price_list_id'));
    }
}
