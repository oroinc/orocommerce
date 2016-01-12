<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Bundle\DoctrineBundle\Registry;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Builder\AccountCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use OroB2B\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountGroupCombinedPriceListsBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $combinedPriceListClass;

    protected function setUp()
    {
        $this->combinedPriceListClass = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList';
    }

    /**
     * @dataProvider testBuildDataProvider
     * @param $websiteId
     * @param $accountGroupId
     * @param $currentCPLId
     * @param $priceListCollection
     * @param $actualCPLId
     */
    public function testBuild($websiteId, $accountGroupId, $priceListCollection, $currentCPLId, $actualCPLId)
    {
        $accountGroupCPLBuilder = $this->getAccountCPLBuilderMock();
        $priceListCollectionProvider = $this->getPriceListCollectionProviderMock($priceListCollection);
        $CPLProvider = $this->getCombinedPriceListProviderMock($priceListCollection, $actualCPLId);
        $garbageCollector = $this->getGarbageCollectorMock(true);

        /**
         * @var $website Website|\PHPUnit_Framework_MockObject_MockObject
         */
        $website = $this->getMock('OroB2B\Bundle\WebsiteBundle\Entity\Website');
        $website->expects($this->any())->method('getId')->willReturn($websiteId);

        /**
         * @var $accountGroup AccountGroup|\PHPUnit_Framework_MockObject_MockObject
         */
        $accountGroup = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountGroup');
        $accountGroup->expects($this->any())->method('getId')->willReturn($accountGroupId);

        $registry = $this->getRegistryWithRepository(null, $currentCPLId, $actualCPLId);
        $CPLBuilder = new AccountGroupCombinedPriceListsBuilder($registry);
        $CPLBuilder->setAccountCombinedPriceListsBuilder($accountGroupCPLBuilder);
        $CPLBuilder->setPriceListCollectionProvider($priceListCollectionProvider);
        $CPLBuilder->setCombinedPriceListProvider($CPLProvider);
        $CPLBuilder->setCombinedPriceListGarbageCollector($garbageCollector);

        $class = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToAccountGroup';
        $CPLBuilder->setCombinedPriceListToAccountGroupClassName($class);
        $class = 'OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup';
        $CPLBuilder->setPriceListToAccountGroupClassName($class);

        $CPLBuilder->build($accountGroup, $website);
    }

    /**
     * @dataProvider testBuildDataProvider
     * @param $websiteId
     * @param $accountGroupId
     * @param $currentCPLId
     * @param $priceListCollection
     * @param $actualCPLId
     */
    public function testBuildForAll($websiteId, $accountGroupId, $priceListCollection, $currentCPLId, $actualCPLId)
    {
        $accountCPLBuilder = $this->getAccountCPLBuilderMock();
        $priceListCollectionProvider = $this->getPriceListCollectionProviderMock($priceListCollection);
        $CPLProvider = $this->getCombinedPriceListProviderMock($priceListCollection, $actualCPLId);
        $garbageCollector = $this->getGarbageCollectorMock(false);

        /**
         * @var $website Website|\PHPUnit_Framework_MockObject_MockObject
         */
        $website = $this->getMock('OroB2B\Bundle\WebsiteBundle\Entity\Website');
        $website->expects($this->any())->method('getId')->willReturn($websiteId);

        /**
         * @var $accountGroup AccountGroup|\PHPUnit_Framework_MockObject_MockObject
         */
        $accountGroup = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountGroup');
        $accountGroup->expects($this->any())->method('getId')->willReturn($accountGroupId);

        $registry = $this->getRegistryWithRepository($accountGroup, $currentCPLId, $actualCPLId);
        $CPLBuilder = new AccountGroupCombinedPriceListsBuilder($registry);
        $CPLBuilder->setAccountCombinedPriceListsBuilder($accountCPLBuilder);
        $CPLBuilder->setPriceListCollectionProvider($priceListCollectionProvider);
        $CPLBuilder->setCombinedPriceListProvider($CPLProvider);
        $CPLBuilder->setCombinedPriceListGarbageCollector($garbageCollector);

        $class = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToAccountGroup';
        $CPLBuilder->setCombinedPriceListToAccountGroupClassName($class);
        $class = 'OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup';
        $CPLBuilder->setPriceListToAccountGroupClassName($class);

        $CPLBuilder->buildByWebsite($website);
    }

    /**
     * @return array
     */
    public function testBuildDataProvider()
    {
        return [
            'no changes' => [
                'websiteId' => 1,
                'accountGroupId' => 1,
                'priceListCollection' => [1,2,3],
                'currentCPLId' => 1,
                'actualCPLId' => 1,
            ],
            'change cpl' => [
                'websiteId' => 1,
                'accountGroupId' => 1,
                'priceListCollection' => [1,2,3],
                'currentCPLId' => 2,
                'actualCPLId' => 1,
            ],
            'new cpl' => [
                'websiteId' => 1,
                'accountGroupId' => 1,
                'priceListCollection' => [1,2,3],
                'currentCPLId' => null,
                'actualCPLId' => 1,
            ],
        ];
    }

    /**
     * @param $priceListCollection
     * @return \PHPUnit_Framework_MockObject_MockObject|PriceListCollectionProvider
     */
    protected function getPriceListCollectionProviderMock($priceListCollection)
    {
        $providerClass = 'OroB2B\Bundle\PricingBundle\Provider\PriceListCollectionProvider';
        $priceListCollectionProvider = $this->getMockBuilder($providerClass)
            ->disableOriginalConstructor()
            ->getMock();

        $priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByAccountGroup')
            ->willReturn($priceListCollection);

        return $priceListCollectionProvider;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AccountCombinedPriceListsBuilder
     */
    protected function getAccountCPLBuilderMock()
    {
        $accountCPLBuilderClass = 'OroB2B\Bundle\PricingBundle\Builder\AccountCombinedPriceListsBuilder';
        $accountGroupCPLBuilder = $this->getMockBuilder($accountCPLBuilderClass)
            ->disableOriginalConstructor()
            ->getMock();
        $accountGroupCPLBuilder->expects($this->once())->method('buildByAccountGroup');

        return $accountGroupCPLBuilder;
    }

    /**
     * @param $collection
     * @param $actualCPLId
     * @return CombinedPriceListProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCombinedPriceListProviderMock($collection, $actualCPLId)
    {
        $actualCPL = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList')
            ->disableOriginalConstructor()
            ->getMock();

        $actualCPL->expects($this->any())->method('getId')->willReturn($actualCPLId);

        $CPLProvider = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $CPLProvider->expects($this->once())
            ->method('getCombinedPriceList')
            ->with($collection)
            ->willReturn($actualCPL);

        return $CPLProvider;
    }

    /**
     * @param $buildForOne
     * @return CombinedPriceListGarbageCollector|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getGarbageCollectorMock($buildForOne)
    {
        $collector = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector')
            ->disableOriginalConstructor()
            ->getMock();

        if ($buildForOne) {
            $collector->expects($this->once())->method('cleanCombinedPriceLists');
        } else {
            $collector->expects($this->never())->method('cleanCombinedPriceLists');
        }

        return $collector;
    }

    /**
     * @param $accountGroup
     * @param $currentCPLId
     * @param $actualCPLId
     * @return Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRegistryWithRepository($accountGroup, $currentCPLId, $actualCPLId)
    {
        $PLToAccountGroupClass = 'OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup';
        $PLToAccountGroupRepository = $this->getPriceListToAccountGroupRepositoryMock($accountGroup);

        $CPLToAccountGroupClass = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToAccountGroup';
        $CPLRepository = $this->getCombinedPriceListToAccountGroupRepositoryMock($currentCPLId, $actualCPLId);

        /**
         * @var $registry \PHPUnit_Framework_MockObject_MockObject|Registry
         */
        $registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        if ($currentCPLId != $actualCPLId) {
            $em->expects($this->once())->method('flush');
        } else {
            $em->expects($this->never())->method('flush');
        }

        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [$PLToAccountGroupClass, $PLToAccountGroupRepository],
                [$CPLToAccountGroupClass, $CPLRepository],
            ]);

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);



        return $registry;
    }

    /**
     * @param $accountGroup
     * @return PriceListToAccountGroupRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPriceListToAccountGroupRepositoryMock($accountGroup)
    {
        $class = 'OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository';
        $PLToAccountGroupRepository = $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($accountGroup) {
            $relation = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup');
            $relation->expects($this->exactly(2))->method('getAccountGroup')->willReturn($accountGroup);

            $PLToAccountGroupRepository->expects($this->once())->method('getPriceListToAccountGroupByWebsiteIterator')
                ->willReturn([$relation]);
        } else {
            $PLToAccountGroupRepository->expects($this->never())->method('getPriceListToAccountGroupByWebsiteIterator');
        }

        return $PLToAccountGroupRepository;
    }

    /**
     * @param $currentCPLId
     * @param $actualCPLId
     * @return PriceListToAccountGroupRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCombinedPriceListToAccountGroupRepositoryMock($currentCPLId, $actualCPLId)
    {
        $CPLToAccountGroupRepoClass = 'OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository';
        $CPLToAccountGroupRepository = $this->getMockBuilder($CPLToAccountGroupRepoClass)
            ->disableOriginalConstructor()
            ->getMock();

        $relation = null;
        if ($currentCPLId == $actualCPLId) {
            $relation = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup');
        }
        $CPLToAccountGroupRepository->expects($this->once())
            ->method('findByPrimaryKey')
            ->willReturn($relation);

        return $CPLToAccountGroupRepository;
    }
}
