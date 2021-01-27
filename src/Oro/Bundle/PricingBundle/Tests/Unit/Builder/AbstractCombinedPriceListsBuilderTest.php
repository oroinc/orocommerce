<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\PricingStrategy\PriceCombiningStrategyInterface;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
abstract class AbstractCombinedPriceListsBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var StrategyRegister|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $strategyRegister;

    /**
     * @var CombinedPriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $triggerHandler;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var PriceListCollectionProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $priceListCollectionProvider;

    /**
     * @var CombinedPriceListProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $combinedPriceListProvider;

    /**
     * @var string
     */
    protected $priceListToEntityClass = 'someClass';

    /**
     * @var string
     */
    protected $combinedPriceListClass = 'someOtherClass';

    /**
     * @var string
     */
    protected $combinedPriceListToEntityClass = 'someOtherClass1';

    /**
     * @var string
     */
    protected $fallbackClass = 'someOtherClass2';

    /**
     * @var CombinedPriceListRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $combinedPriceListRepository;

    /**
     * @var PriceListRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $combinedPriceListToEntityRepository;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $priceListToEntityRepository;

    /**
     * @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $fallbackRepository;

    /**
     * @var CombinedPriceListScheduleResolver
     */
    protected $cplScheduleResolver;

    /**
     * @var PriceCombiningStrategyInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $combiningStrategy;

    /**
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $combinedPriceListEm;

    /**
     * @return string
     */
    abstract protected function getPriceListToEntityRepositoryClass();

    /**
     * @return string
     */
    protected function getPriceListFallbackRepositoryClass()
    {
        return EntityRepository::class;
    }

    /**
     * @return array
     */
    public function trueFalseDataProvider()
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->priceListCollectionProvider = $this->createMock(PriceListCollectionProvider::class);
        $this->combinedPriceListProvider = $this->createMock(CombinedPriceListProvider::class);
        $this->combinedPriceListRepository = $this->createMock(CombinedPriceListRepository::class);
        $this->fallbackRepository = $this->createMock($this->getPriceListFallbackRepositoryClass());
        $fallbackEm = $this->createMock(ObjectManager::class);
        $fallbackEm->expects($this->any())
            ->method('getRepository')
            ->with($this->fallbackClass)
            ->willReturn($this->fallbackRepository);

        $this->combinedPriceListEm = $this->createMock(EntityManager::class);
        $this->combinedPriceListEm->expects($this->any())
            ->method('getRepository')
            ->with($this->combinedPriceListClass)
            ->willReturn($this->combinedPriceListRepository);

        $this->priceListToEntityRepository = $this->createMock($this->getPriceListToEntityRepositoryClass());
        $priceListToEntityEm = $this->createMock(ObjectManager::class);
        $priceListToEntityEm->expects($this->any())
            ->method('getRepository')
            ->with($this->priceListToEntityClass)
            ->willReturn($this->priceListToEntityRepository);

        $this->combinedPriceListToEntityRepository = $this->createMock($this->getPriceListToEntityRepositoryClass());
        $combinedPriceListToEntityEm = $this->createMock(ObjectManager::class);
        $combinedPriceListToEntityEm->expects($this->any())
            ->method('getRepository')
            ->with($this->combinedPriceListToEntityClass)
            ->willReturn($this->combinedPriceListToEntityRepository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap(
                [
                    [$this->combinedPriceListClass, $this->combinedPriceListEm],
                    [$this->priceListToEntityClass, $priceListToEntityEm],
                    [$this->combinedPriceListToEntityClass, $combinedPriceListToEntityEm],
                    [$this->fallbackClass, $fallbackEm],
                ]
            );

        $this->cplScheduleResolver = $this->createMock(CombinedPriceListScheduleResolver::class);
        $this->combiningStrategy = $this->createMock(PriceCombiningStrategyInterface::class);
        $this->strategyRegister = $this->createMock(StrategyRegister::class);
        $this->strategyRegister->method('getCurrentStrategy')->willReturn($this->combiningStrategy);
        $this->triggerHandler = $this->getMockBuilder(CombinedPriceListTriggerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getPriceListSequenceMember()
    {
        return $this->createMock(PriceListSequenceMember::class);
    }

    protected function configureTransactionWrappingForOneCall()
    {
        $this->combinedPriceListEm
            ->expects($this->once())
            ->method('beginTransaction');

        $this->combinedPriceListEm
            ->expects($this->once())
            ->method('commit');

        $this->combinedPriceListEm
            ->expects($this->never())
            ->method('rollback');
    }
}
