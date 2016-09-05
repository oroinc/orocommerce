<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListScheduleRepository;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Resolver\PriceListScheduleResolver;

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

        $className = 'Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository';
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
        $className = 'Oro\Bundle\PricingBundle\Resolver\PriceListScheduleResolver';
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
        $className = 'Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository';
        $this->combinedPriceListRepository = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
        $this->combinedPriceListRepository
            ->method('getCombinedPriceListsByPriceList')
            ->willReturn([new CombinedPriceList()]);
    }

    protected function createPriceListScheduleRepositoryMock()
    {
        $className = 'Oro\Bundle\PricingBundle\Entity\Repository\PriceListScheduleRepository';
        $this->priceListScheduleRepository = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceListScheduleRepository->method('getSchedulesByCPL')->willReturn([]);
    }

    protected function createCombinedPriceListToPriceListRepositoryMock()
    {
        $className = 'Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository';
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
                ['OroPricingBundle:CombinedPriceListActivationRule', $this->CPLActivationRuleRepository],
                ['OroPricingBundle:CombinedPriceListToPriceList', $this->CPLToPriceListRepository],
                ['OroPricingBundle:PriceListSchedule', $this->priceListScheduleRepository],
                ['OroPricingBundle:CombinedPriceList', $this->combinedPriceListRepository],
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
        $className = 'Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider';
        $this->combinedPriceListProvider = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
        $this->combinedPriceListProvider->method('getCombinedPriceList')
            ->with([])
            ->willReturn(new CombinedPriceList());
    }
}
