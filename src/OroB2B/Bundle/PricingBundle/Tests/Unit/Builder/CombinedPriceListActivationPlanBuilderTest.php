<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListScheduleRepository;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use OroB2B\Bundle\PricingBundle\Resolver\PriceListScheduleResolver;

class CombinedPriceListActivationPlanBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var PriceListScheduleResolver
     */
    protected $schedulerResolver;

    /**
     * @var CombinedPriceListProvider
     */
    protected $combinedPriceListProvider;

    /**
     * @var CombinedPriceListRepository
     */
    protected $combinedPriceListRepository;

    /**
     * @var PriceListScheduleRepository
     */
    protected $priceListScheduleRepository;

    /**
     * @var CombinedPriceListToPriceListRepository
     */
    protected $CPLToPriceListRepository;

    /**
     * @var CombinedPriceListActivationRuleRepository
     */
    protected $CPLActivationRuleRepository;

    /**
     * @var CombinedPriceListActivationPlanBuilder
     */
    protected $builder;

    public function setUp()
    {
        $this->createCombinedPriceListProviderMock();
        $this->createCombinedPriceListRepositoryMock();
        $this->schedulerResolverMock();
        $this->createPriceListScheduleRepositoryMock();
        $this->createCombinedPriceListToPriceListRepositoryMock();

        $className = 'OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository';
        $this->CPLActivationRuleRepository = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();

        $this->createDoctrineHelperMock();

        $this->builder = new CombinedPriceListActivationPlanBuilder(
            $this->doctrineHelper,
            $this->schedulerResolver
        );
        $this->builder->setProvider($this->combinedPriceListProvider);
    }

    public function test()
    {
        $this->builder->buildByPriceList(new PriceList());
    }

    protected function schedulerResolverMock()
    {
        $className = 'OroB2B\Bundle\PricingBundle\Resolver\PriceListScheduleResolver';
        $this->schedulerResolver = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
        $priceListId = 1;
        $timestamp = 100;
        $this->schedulerResolver->method('mergeSchedule')->willReturn([
            $timestamp => [
                PriceListScheduleResolver::ACTIVATE_AT_KEY => null,
                PriceListScheduleResolver::EXPIRE_AT_KEY => null,
                PriceListScheduleResolver::PRICE_LISTS_KEY => [$priceListId],
            ]
        ]);
    }

    protected function createCombinedPriceListRepositoryMock()
    {
        $className = 'OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository';
        $this->combinedPriceListRepository = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
        $this->combinedPriceListRepository
            ->method('getCombinedPriceListsByPriceList')
            ->willReturn([new CombinedPriceList()]);
    }

    protected function createPriceListScheduleRepositoryMock()
    {
        $className = 'OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListScheduleRepository';
        $this->priceListScheduleRepository = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceListScheduleRepository->method('getSchedulesByCPL')->willReturn([]);
    }

    protected function createCombinedPriceListToPriceListRepositoryMock()
    {
        $className = 'OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository';
        $this->CPLToPriceListRepository = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
        $this->CPLToPriceListRepository->method('getPriceListRelations')->willReturn([]);
    }

    protected function createDoctrineHelperMock()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->method('getEntityRepository')
            ->willReturnMap([
                ['OroB2BPricingBundle:CombinedPriceListActivationRule', $this->CPLActivationRuleRepository],
                ['OroB2BPricingBundle:CombinedPriceListToPriceList', $this->CPLToPriceListRepository],
                ['OroB2BPricingBundle:PriceListSchedule', $this->priceListScheduleRepository],
                ['OroB2BPricingBundle:CombinedPriceList', $this->combinedPriceListRepository],
            ]);
        /** @var \Doctrine\ORM\EntityManager| \PHPUnit_Framework_MockObject_MockObject $manager */
        $manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->once())->method('persist');
        $manager->expects($this->once())->method('flush');
        $this->doctrineHelper->method('getEntityManagerForClass')->willReturn($manager);
    }

    protected function createCombinedPriceListProviderMock()
    {
        $className = 'OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider';
        $this->combinedPriceListProvider = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
        $this->combinedPriceListProvider->method('getCombinedPriceList')
            ->with([])
            ->willReturn(new CombinedPriceList());
    }
}
