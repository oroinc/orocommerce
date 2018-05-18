<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\PricingStrategy\PriceCombiningStrategyInterface;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
abstract class AbstractCombinedPriceListsBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StrategyRegister|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $strategyRegister;

    /**
     * @var CombinedPriceListTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $triggerHandler;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var PriceListCollectionProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceListCollectionProvider;

    /**
     * @var CombinedPriceListProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $combinedPriceListProvider;

    /**
     * @var CombinedPriceListGarbageCollector|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $garbageCollector;

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
     * @var CombinedPriceListRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $combinedPriceListRepository;

    /**
     * @var PriceListRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $combinedPriceListToEntityRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceListToEntityRepository;

    /**
     * @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fallbackRepository;

    /**
     * @var CombinedPriceListScheduleResolver
     */
    protected $cplScheduleResolver;

    /**
     * @var PriceCombiningStrategyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $combiningStrategy;

    /**
     * @return string
     */
    abstract protected function getPriceListToEntityRepositoryClass();


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
    protected function setUp()
    {
        $this->registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->priceListCollectionProvider = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->combinedPriceListProvider = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->garbageCollector = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->combinedPriceListRepository = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fallbackRepository = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $fallbackEm = $this->createMock('\Doctrine\Common\Persistence\ObjectManager');
        $fallbackEm->expects($this->any())
            ->method('getRepository')
            ->with($this->fallbackClass)
            ->will($this->returnValue($this->fallbackRepository));

        $combinedPriceListEm = $this->createMock('\Doctrine\Common\Persistence\ObjectManager');
        $combinedPriceListEm->expects($this->any())
            ->method('getRepository')
            ->with($this->combinedPriceListClass)
            ->will($this->returnValue($this->combinedPriceListRepository));

        $this->priceListToEntityRepository = $this->getMockBuilder($this->getPriceListToEntityRepositoryClass())
            ->disableOriginalConstructor()
            ->getMock();

        $priceListToEntityEm = $this->createMock('\Doctrine\Common\Persistence\ObjectManager');
        $priceListToEntityEm->expects($this->any())
            ->method('getRepository')
            ->with($this->priceListToEntityClass)
            ->will($this->returnValue($this->priceListToEntityRepository));

        $this->combinedPriceListToEntityRepository = $this->getMockBuilder($this->getPriceListToEntityRepositoryClass())
            ->disableOriginalConstructor()
            ->getMock();
        $combinedPriceListToEntityEm = $this->createMock('\Doctrine\Common\Persistence\ObjectManager');
        $combinedPriceListToEntityEm->expects($this->any())
            ->method('getRepository')
            ->with($this->combinedPriceListToEntityClass)
            ->will($this->returnValue($this->combinedPriceListToEntityRepository));

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->will(
                $this->returnValueMap(
                    [
                        [$this->combinedPriceListClass, $combinedPriceListEm],
                        [$this->priceListToEntityClass, $priceListToEntityEm],
                        [$this->combinedPriceListToEntityClass, $combinedPriceListToEntityEm],
                        [$this->fallbackClass, $fallbackEm],
                    ]
                )
            );

        $className = 'Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver';
        $this->cplScheduleResolver = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
        $this->combiningStrategy = self::createMock(PriceCombiningStrategyInterface::class);
        $this->strategyRegister = self::createMock(StrategyRegister::class);
        $this->strategyRegister->method('getCurrentStrategy')->willReturn($this->combiningStrategy);
        $this->triggerHandler = $this->getMockBuilder(CombinedPriceListTriggerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPriceListSequenceMember()
    {
        return $this->getMockBuilder('Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
