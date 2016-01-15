<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ManagerRegistry;

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
    protected $combinedPriceListClass = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList';

    /**
     * @var string
     */
    protected $priceListToWebsiteClassName = 'OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite';

    /**
     * @var string
     */
    protected $combinedPriceListToWebsiteClassName = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite';

    /**
     * @var WebsiteCombinedPriceListsBuilder
     */
    protected $builder;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var PriceListCollectionProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceListCollectionProvider;

    /**
     * @var AccountGroupCombinedPriceListsBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $accountGroupCPLBuilder;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->accountGroupCPLBuilder = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceListCollectionProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\PriceListCollectionProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new WebsiteCombinedPriceListsBuilder($this->registry);
        $this->builder
            ->setCombinedPriceListToWebsiteClassName($this->combinedPriceListToWebsiteClassName)
            ->setPriceListToWebsiteClassName($this->priceListToWebsiteClassName)
            ->setAccountGroupCombinedPriceListsBuilder($this->accountGroupCPLBuilder)
            ->setPriceListCollectionProvider($this->priceListCollectionProvider);
    }

    /**
     * @dataProvider testBuildDataProvider
     * @param int $websiteId
     * @param int $currentCPLId
     * @param array $priceListCollection
     * @param int $actualCPLId
     */
    public function testBuild($websiteId, $priceListCollection, $currentCPLId, $actualCPLId)
    {
        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByWebsite')
            ->willReturn($priceListCollection);

        $this->accountGroupCPLBuilder->expects($this->once())
            ->method('build');



        $cplProvider = $this->getCombinedPriceListProviderMock($priceListCollection, $actualCPLId);
        $garbageCollector = $this->getGarbageCollectorMock(true);
        $this->builder->setCombinedPriceListProvider($cplProvider)
            ->setCombinedPriceListGarbageCollector($garbageCollector);

        $website = $this->getWebsiteMock($websiteId);
        $this->prepareRepositoryAssertions(null, $currentCPLId, $actualCPLId);

        $this->builder->build($website);
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
     * @param Website $website
     * @param int $currentCPLId
     * @param int $actualCPLId
     * @return Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareRepositoryAssertions($website, $currentCPLId, $actualCPLId)
    {
        $PLToWebsiteRepository = $this->getPriceListToWebsiteRepositoryMock($website);
        $CPLToWebsiteRepository = $this->getCombinedPriceListToWebsiteRepositoryMock($currentCPLId, $actualCPLId);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        if ($currentCPLId !== $actualCPLId) {
            $em->expects($this->once())->method('flush');
        } else {
            $em->expects($this->never())->method('flush');
        }

        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [$this->priceListToWebsiteClassName, $PLToWebsiteRepository],
                [$this->combinedPriceListToWebsiteClassName, $CPLToWebsiteRepository],
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);
    }

    /**
     * @param Website $website
     * @return PriceListToWebsiteRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPriceListToWebsiteRepositoryMock($website)
    {
        $PLToWebsiteRepository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository')
            ->disableOriginalConstructor()
            ->getMock();

        if ($website) {
            $PLToWebsiteRepository->expects($this->once())->method('getWebsiteIteratorByFallback')
                ->willReturn([$website]);
        } else {
            $PLToWebsiteRepository->expects($this->never())->method('getWebsiteIteratorByFallback');
        }

        return $PLToWebsiteRepository;
    }

    /**
     * @param int $currentCPLId
     * @param int $actualCPLId
     * @return PriceListToWebsiteRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCombinedPriceListToWebsiteRepositoryMock($currentCPLId, $actualCPLId)
    {
        $CPLToWebsiteRepository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $relation = null;
        if ($currentCPLId === $actualCPLId) {
            $relation = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite');
        }
        $CPLToWebsiteRepository->expects($this->once())
            ->method('findByPrimaryKey')
            ->willReturn($relation);

        return $CPLToWebsiteRepository;
    }

    /**
     * @param int $websiteId
     * @return Website|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getWebsiteMock($websiteId)
    {
        $website = $this->getMock('OroB2B\Bundle\WebsiteBundle\Entity\Website');
        $website->expects($this->any())->method('getId')->willReturn($websiteId);

        return $website;
    }
}
