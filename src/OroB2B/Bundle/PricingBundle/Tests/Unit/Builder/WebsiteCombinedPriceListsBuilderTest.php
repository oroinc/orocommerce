<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Bundle\DoctrineBundle\Registry;

use OroB2B\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use OroB2B\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteCombinedPriceListsBuilderTest extends AbstractCombinedPriceListsBuilderTest
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
     * @param $currentCPLId
     * @param $priceListCollection
     * @param $actualCPLId
     */
    public function testBuild($websiteId, $priceListCollection, $currentCPLId, $actualCPLId)
    {
        $accountGroupCPLBuilder = $this->getAccountGroupCPLBuilderMock();
        $priceListCollectionProvider = $this->getPriceListCollectionProviderMock($priceListCollection);
        $CPLProvider = $this->getCombinedPriceListProviderMock($priceListCollection, $actualCPLId);
        $garbageCollector = $this->getGarbageCollectorMock(true);

        /**
         * @var $website Website|\PHPUnit_Framework_MockObject_MockObject
         */
        $website = $this->getMock('OroB2B\Bundle\WebsiteBundle\Entity\Website');
        $website->expects($this->any())->method('getId')->willReturn($websiteId);
        $registry = $this->getRegistryWithRepository(null, $currentCPLId, $actualCPLId);
        $CPLBuilder = new WebsiteCombinedPriceListsBuilder($registry);
        $CPLBuilder->setAccountGroupCombinedPriceListsBuilder($accountGroupCPLBuilder);
        $CPLBuilder->setPriceListCollectionProvider($priceListCollectionProvider);
        $CPLBuilder->setCombinedPriceListProvider($CPLProvider);
        $CPLBuilder->setCombinedPriceListGarbageCollector($garbageCollector);

        $class = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite';
        $CPLBuilder->setCombinedPriceListToWebsiteClassName($class);
        $class = 'OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite';
        $CPLBuilder->setPriceListToWebsiteClassName($class);

        $CPLBuilder->build($website);
    }

    /**
     * @dataProvider testBuildDataProvider
     * @param $websiteId
     * @param $currentCPLId
     * @param $priceListCollection
     * @param $actualCPLId
     */
    public function testBuildForAll($websiteId, $priceListCollection, $currentCPLId, $actualCPLId)
    {
        $accountGroupCPLBuilder = $this->getAccountGroupCPLBuilderMock();
        $priceListCollectionProvider = $this->getPriceListCollectionProviderMock($priceListCollection);
        $CPLProvider = $this->getCombinedPriceListProviderMock($priceListCollection, $actualCPLId);
        $garbageCollector = $this->getGarbageCollectorMock(false);

        /**
         * @var $website Website|\PHPUnit_Framework_MockObject_MockObject
         */
        $website = $this->getMock('OroB2B\Bundle\WebsiteBundle\Entity\Website');
        $website->expects($this->any())->method('getId')->willReturn($websiteId);
        $registry = $this->getRegistryWithRepository($website, $currentCPLId, $actualCPLId);
        $CPLBuilder = new WebsiteCombinedPriceListsBuilder($registry);
        $CPLBuilder->setAccountGroupCombinedPriceListsBuilder($accountGroupCPLBuilder);
        $CPLBuilder->setPriceListCollectionProvider($priceListCollectionProvider);
        $CPLBuilder->setCombinedPriceListProvider($CPLProvider);
        $CPLBuilder->setCombinedPriceListGarbageCollector($garbageCollector);

        $class = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite';
        $CPLBuilder->setCombinedPriceListToWebsiteClassName($class);
        $class = 'OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite';
        $CPLBuilder->setPriceListToWebsiteClassName($class);

        $CPLBuilder->buildForAll();
    }

    /**
     * @return array
     */
    public function testBuildDataProvider()
    {
        return [
            'no changes' => [
                'websiteId' => 1,
                'priceListCollection' => [1,2,3],
                'currentCPLId' => 1,
                'actualCPLId' => 1,
            ],
            'change cpl' => [
                'websiteId' => 1,
                'priceListCollection' => [1,2,3],
                'currentCPLId' => 2,
                'actualCPLId' => 1,
            ],
            'new cpl' => [
                'websiteId' => 1,
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
            ->method('getPriceListsByWebsite')
            ->willReturn($priceListCollection);

        return $priceListCollectionProvider;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AccountGroupCombinedPriceListsBuilder
     */
    protected function getAccountGroupCPLBuilderMock()
    {
        $accountGroupCPLBuilderClass = 'OroB2B\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder';
        $accountGroupCPLBuilder = $this->getMockBuilder($accountGroupCPLBuilderClass)
            ->disableOriginalConstructor()
            ->getMock();
        $accountGroupCPLBuilder->expects($this->once())->method('buildByWebsite');

        return $accountGroupCPLBuilder;
    }

    /**
     * @param $website
     * @param $currentCPLId
     * @param $actualCPLId
     * @return Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRegistryWithRepository($website, $currentCPLId, $actualCPLId)
    {
        $PLToWebsiteClass = 'OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite';
        $PLToWebsiteRepository = $this->getPriceListToWebsiteRepositoryMock($website);

        $CPLToWebsiteClass = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite';
        $CPLToWebsiteRepository = $this->getCombinedPriceListToWebsiteRepositoryMock($currentCPLId, $actualCPLId);

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
                [$PLToWebsiteClass, $PLToWebsiteRepository],
                [$CPLToWebsiteClass, $CPLToWebsiteRepository],
            ]);

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        return $registry;
    }

    /**
     * @param $website
     * @return PriceListToWebsiteRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPriceListToWebsiteRepositoryMock($website)
    {
        $PLToWebsiteRepositoryClass = 'OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository';
        $PLToWebsiteRepository = $this->getMockBuilder($PLToWebsiteRepositoryClass)
            ->disableOriginalConstructor()
            ->getMock();

        if ($website) {
            $relation = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite');
            $relation->expects($this->exactly(2))->method('getWebsite')->willReturn($website);

            $PLToWebsiteRepository->expects($this->once())->method('getPriceListToWebsiteIterator')
                ->willReturn([$relation]);
        } else {
            $PLToWebsiteRepository->expects($this->never())->method('getPriceListToWebsiteIterator');
        }

        return $PLToWebsiteRepository;
    }

    /**
     * @param $currentCPLId
     * @param $actualCPLId
     * @return PriceListToWebsiteRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCombinedPriceListToWebsiteRepositoryMock($currentCPLId, $actualCPLId)
    {
        $CPLToWebsiteRepoClass = 'OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository';
        $CPLToWebsiteRepository = $this->getMockBuilder($CPLToWebsiteRepoClass)
            ->disableOriginalConstructor()
            ->getMock();

        $relation = null;
        if ($currentCPLId == $actualCPLId) {
            $relation = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite');
        }
        $CPLToWebsiteRepository->expects($this->once())
            ->method('findByPrimaryKey')
            ->willReturn($relation);

        return $CPLToWebsiteRepository;
    }
}
