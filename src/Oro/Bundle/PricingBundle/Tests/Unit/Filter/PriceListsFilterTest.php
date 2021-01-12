<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Filter;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\Filter\PriceListsFilter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class PriceListsFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface */
    private $form;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormFactoryInterface */
    private $formFactory;

    /** @var Registry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var PriceListsFilter */
    private $priceListsFilter;

    public function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrine = $this->createMock(Registry::class);

        $this->form = $this->createMock(FormInterface::class);
        $this->formFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->form);

        $this->priceListsFilter = new PriceListsFilter(
            $this->formFactory,
            new FilterUtility()
        );
        $this->priceListsFilter->setDoctrine($this->doctrine);
        $this->priceListsFilter->setRegistry($this->doctrine);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Parameter relation_class_name is required
     */
    public function testInitEntityAliasExceptions()
    {
        $this->priceListsFilter->init('price_list', []);
    }

    public function testApplyNoData()
    {
        $ds = $this->createMock(OrmFilterDatasourceAdapter::class);

        $this->priceListsFilter->init(
            'price_list',
            [
                PriceListsFilter::RELATION_CLASS_NAME_PARAMETER => PriceListToCustomer::class
            ]
        );

        $this->assertFalse($this->priceListsFilter->apply($ds, null));
    }

    public function testApply()
    {
        $data = [
            'type' => null,
            'value' => [new PriceList()]
        ];

        $ds = $this->createMock(OrmFilterDatasourceAdapter::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $ds->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $repository = $this->createMock(PriceListToCustomerRepository::class);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(PriceListToCustomer::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('restrictByPriceList');

        $this->priceListsFilter->init(
            'price_list',
            [
                PriceListsFilter::RELATION_CLASS_NAME_PARAMETER => PriceListToCustomer::class
            ]
        );

        $this->assertTrue($this->priceListsFilter->apply($ds, $data));
    }
}
