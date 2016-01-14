<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Bundle\DoctrineBundle\Registry;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Builder\AccountCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;
use OroB2B\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountCombinedPriceListsBuilderTest extends AbstractCombinedPriceListsBuilderTest
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
     * @param $accountId
     * @param $currentCPLId
     * @param $priceListCollection
     * @param $actualCPLId
     */
    public function testBuild($websiteId, $accountId, $priceListCollection, $currentCPLId, $actualCPLId)
    {
        $priceListCollectionProvider = $this->getPriceListCollectionProviderMock($priceListCollection);
        $CPLProvider = $this->getCombinedPriceListProviderMock($priceListCollection, $actualCPLId);
        $garbageCollector = $this->getGarbageCollectorMock(true);

        /**
         * @var $website Website|\PHPUnit_Framework_MockObject_MockObject
         */
        $website = $this->getMock('OroB2B\Bundle\WebsiteBundle\Entity\Website');
        $website->expects($this->any())->method('getId')->willReturn($websiteId);

        /**
         * @var $account Account|\PHPUnit_Framework_MockObject_MockObject
         */
        $account = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Account');
        $account->expects($this->any())->method('getId')->willReturn($accountId);

        $registry = $this->getRegistryWithRepository(null, $currentCPLId, $actualCPLId);
        $CPLBuilder = new AccountCombinedPriceListsBuilder($registry);
        $CPLBuilder->setPriceListCollectionProvider($priceListCollectionProvider);
        $CPLBuilder->setCombinedPriceListProvider($CPLProvider);
        $CPLBuilder->setCombinedPriceListGarbageCollector($garbageCollector);

        $class = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToAccount';
        $CPLBuilder->setCombinedPriceListToAccountClassName($class);
        $class = 'OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount';
        $CPLBuilder->setPriceListToAccountClassName($class);

        $CPLBuilder->build($website, $account);
    }

    /**
     * @dataProvider testBuildForAllDataProvider
     * @param $websiteId
     * @param $accountGroupId
     * @param $accountId
     * @param $currentCPLId
     * @param $priceListCollection
     * @param $actualCPLId
     */
    public function testBuildForAll(
        $websiteId,
        $accountGroupId,
        $accountId,
        $priceListCollection,
        $currentCPLId,
        $actualCPLId
    ) {
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

        /**
         * @var $account AccountGroup|\PHPUnit_Framework_MockObject_MockObject
         */
        $account = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Account');
        $account->expects($this->any())->method('getId')->willReturn($accountId);

        $registry = $this->getRegistryWithRepository($account, $currentCPLId, $actualCPLId);
        $CPLBuilder = new AccountCombinedPriceListsBuilder($registry);
        $CPLBuilder->setPriceListCollectionProvider($priceListCollectionProvider);
        $CPLBuilder->setCombinedPriceListProvider($CPLProvider);
        $CPLBuilder->setCombinedPriceListGarbageCollector($garbageCollector);

        $class = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToAccount';
        $CPLBuilder->setCombinedPriceListToAccountClassName($class);
        $class = 'OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount';
        $CPLBuilder->setPriceListToAccountClassName($class);

        $CPLBuilder->buildByAccountGroup($website, $accountGroup);
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
     * @return array
     */
    public function testBuildForAllDataProvider()
    {
        return [
            'no changes' => [
                'websiteId' => 1,
                'accountGroupId' => 1,
                'accountId' => 1,
                'priceListCollection' => [1,2,3],
                'currentCPLId' => 1,
                'actualCPLId' => 1,
            ],
            'change cpl' => [
                'websiteId' => 1,
                'accountGroupId' => 1,
                'accountId' => 2,
                'priceListCollection' => [1,2,3],
                'currentCPLId' => 2,
                'actualCPLId' => 1,
            ],
            'new cpl' => [
                'websiteId' => 1,
                'accountGroupId' => 1,
                'accountId' => 3,
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
            ->method('getPriceListsByAccount')
            ->willReturn($priceListCollection);

        return $priceListCollectionProvider;
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
     * @param $account
     * @param $currentCPLId
     * @param $actualCPLId
     * @return Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRegistryWithRepository($account, $currentCPLId, $actualCPLId)
    {
        $PLToAccountClass = 'OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount';
        $PLToAccountRepository = $this->getPriceListToAccountRepositoryMock($account);

        $CPLToAccountClass = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToAccount';
        $CPLRepository = $this->getCombinedPriceListToAccountRepositoryMock($currentCPLId, $actualCPLId);

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
                [$PLToAccountClass, $PLToAccountRepository],
                [$CPLToAccountClass, $CPLRepository],
            ]);

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);



        return $registry;
    }

    /**
     * @param $account
     * @return PriceListToAccountRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPriceListToAccountRepositoryMock($account)
    {
        $class = 'OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository';
        $PLToAccountRepository = $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($account) {
            $PLToAccountRepository->expects($this->once())->method('getAccountIteratorByFallback')
                ->willReturn([$account]);
        } else {
            $PLToAccountRepository->expects($this->never())->method('getAccountIteratorByFallback');
        }

        return $PLToAccountRepository;
    }

    /**
     * @param $currentCPLId
     * @param $actualCPLId
     * @return PriceListToAccountRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCombinedPriceListToAccountRepositoryMock($currentCPLId, $actualCPLId)
    {
        $CPLToAccountRepoClass = 'OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository';
        $CPLToAccountRepository = $this->getMockBuilder($CPLToAccountRepoClass)
            ->disableOriginalConstructor()
            ->getMock();

        $relation = null;
        if ($currentCPLId == $actualCPLId) {
            $relation = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount');
        }
        $CPLToAccountRepository->expects($this->once())
            ->method('findByPrimaryKey')
            ->willReturn($relation);

        return $CPLToAccountRepository;
    }
}
