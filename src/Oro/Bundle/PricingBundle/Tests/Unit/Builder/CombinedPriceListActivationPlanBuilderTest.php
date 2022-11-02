<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListScheduleRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListRelationHelperInterface;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\PricingBundle\Resolver\PriceListScheduleResolver;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class CombinedPriceListActivationPlanBuilderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|MockObject
     */
    protected $doctrineHelper;

    /**
     * @var PriceListScheduleResolver|MockObject
     */
    protected $schedulerResolver;

    /**
     * @var CombinedPriceListProvider|MockObject
     */
    protected $combinedPriceListProvider;

    /**
     * @var CombinedPriceListRelationHelperInterface|MockObject
     */
    protected $relationHelper;

    /**
     * @var CombinedPriceListActivationPlanBuilder
     */
    protected $builder;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->schedulerResolver = $this->createMock(PriceListScheduleResolver::class);
        $this->combinedPriceListProvider = $this->createMock(CombinedPriceListProvider::class);
        $this->relationHelper = $this->createMock(CombinedPriceListRelationHelperInterface::class);

        $this->builder = new CombinedPriceListActivationPlanBuilder(
            $this->doctrineHelper,
            $this->schedulerResolver,
            $this->combinedPriceListProvider,
            $this->relationHelper
        );
    }

    public function testBuildByPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var CombinedPriceList $cpl1 */
        $cpl1 = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $cpl1->setName(md5('1_2'));
        /** @var CombinedPriceList $cpl2 */
        $cpl2 = $this->getEntity(CombinedPriceList::class, ['id' => 2]);
        $cpl2->setName(md5('1'));

        $combinedPriceListRepository = $this->createMock(CombinedPriceListRepository::class);
        $combinedPriceListRepository
            ->method('getCombinedPriceListsByPriceList')
            ->willReturn([$cpl1, $cpl2]);

        $priceListScheduleRepository = $this->createMock(PriceListScheduleRepository::class);
        $cplToPriceListRepository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $cplActivationRuleRepository = $this->createMock(CombinedPriceListActivationRuleRepository::class);
        $this->doctrineHelper->method('getEntityRepository')
            ->willReturnMap([
                [CombinedPriceListActivationRule::class, $cplActivationRuleRepository],
                [CombinedPriceListToPriceList::class, $cplToPriceListRepository],
                [PriceListSchedule::class, $priceListScheduleRepository],
                [CombinedPriceList::class, $combinedPriceListRepository],
            ]);

        $this->relationHelper->expects($this->exactly(2))
            ->method('isFullChainCpl')
            ->withConsecutive(
                [$cpl1],
                [$cpl2]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );
        $this->assertBuildByCombinedPriceList(
            $priceList,
            $cpl1,
            $cplActivationRuleRepository,
            $priceListScheduleRepository,
            $cplToPriceListRepository
        );

        $this->builder->buildByPriceList($priceList);
    }

    public function testBuildByCombinedPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 7]);
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 15]);
        $cpl->setName(md5('7'));

        $priceListScheduleRepository = $this->createMock(PriceListScheduleRepository::class);
        $cplToPriceListRepository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $cplActivationRuleRepository = $this->createMock(CombinedPriceListActivationRuleRepository::class);
        $this->doctrineHelper->method('getEntityRepository')
            ->willReturnMap([
                [CombinedPriceListActivationRule::class, $cplActivationRuleRepository],
                [CombinedPriceListToPriceList::class, $cplToPriceListRepository],
                [PriceListSchedule::class, $priceListScheduleRepository]
            ]);

        $this->assertBuildByCombinedPriceList(
            $priceList,
            $cpl,
            $cplActivationRuleRepository,
            $priceListScheduleRepository,
            $cplToPriceListRepository
        );

        $this->builder->buildByCombinedPriceList($cpl);
    }

    private function assertBuildByCombinedPriceList(
        PriceList $priceList,
        CombinedPriceList $cpl,
        MockObject $cplActivationRuleRepository,
        MockObject $priceListScheduleRepository,
        MockObject $cplToPriceListRepository
    ): void {
        $cplActivationRuleRepository->expects($this->once())
            ->method('deleteRulesByCPL')
            ->with($cpl);

        /** @var PriceListSchedule $schedule */
        $schedule = $this->getEntity(PriceListSchedule::class, ['id' => 42]);
        $schedule->setPriceList($priceList);
        $schedules = [$schedule];
        $priceListScheduleRepository->expects($this->once())
            ->method('getSchedulesByCPL')
            ->willReturn($schedules);
        /** @var CombinedPriceListToPriceList $relation */
        $relation = $this->getEntity(CombinedPriceListToPriceList::class, ['id' => 5]);
        $relation->setCombinedPriceList($cpl);
        $relation->setPriceList($priceList);
        $relation->setMergeAllowed(false);
        $relations = [$relation];
        $cplToPriceListRepository->expects($this->once())
            ->method('getPriceListRelations')
            ->willReturn($relations);

        $timestamp = 100;
        $this->schedulerResolver->expects($this->once())
            ->method('mergeSchedule')
            ->with($schedules, $relations)
            ->willReturn([
                $timestamp => [
                    PriceListScheduleResolver::ACTIVATE_AT_KEY => null,
                    PriceListScheduleResolver::EXPIRE_AT_KEY => null,
                    PriceListScheduleResolver::PRICE_LISTS_KEY => [$priceList->getId()],
                ]
            ]);

        /** @var EntityManager|MockObject $manager */
        $manager = $this->createMock(EntityManager::class);
        $manager->expects($this->once())->method('persist');
        $manager->expects($this->once())->method('flush');
        $this->doctrineHelper
            ->method('getEntityManagerForClass')
            ->willReturn($manager);

        $sequenceMember = new PriceListSequenceMember($priceList, false);
        $this->combinedPriceListProvider->expects($this->once())
            ->method('getCombinedPriceList')
            ->with([$sequenceMember], [CombinedPriceListActivationPlanBuilder::SKIP_ACTIVATION_PLAN_BUILD => true])
            ->willReturn($cpl);
    }
}
