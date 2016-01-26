<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\AccountCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ChangedPriceListCollectionRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class CombinedPriceListQueueConsumerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ChangedPriceListCollectionRepository */
    protected $collectionChangesRepository;
    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;
    /** @var \PHPUnit_Framework_MockObject_MockObject|CombinedPriceListsBuilder */
    protected $cplBuilder;
    /** @var \PHPUnit_Framework_MockObject_MockObject|WebsiteCombinedPriceListsBuilder */
    protected $cplWebsiteBuilder;
    /** @var \PHPUnit_Framework_MockObject_MockObject|AccountGroupCombinedPriceListsBuilder */
    protected $cplAccountGroupBuilder;
    /** @var \PHPUnit_Framework_MockObject_MockObject|AccountCombinedPriceListsBuilder */
    protected $cplAccountBuilder;
    /** @var CombinedPriceListQueueConsumer */
    protected $consumer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $cplBuilderClass = 'OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder';
        $this->cplBuilder = $this->getMockBuilder($cplBuilderClass)
            ->disableOriginalConstructor()
            ->getMock();

        $cplWebsiteBuilderClass = 'OroB2B\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder';
        $this->cplWebsiteBuilder = $this->getMockBuilder($cplWebsiteBuilderClass)
            ->disableOriginalConstructor()
            ->getMock();

        $cplAccountGroupBuilderClass = 'OroB2B\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder';
        $this->cplAccountGroupBuilder = $this->getMockBuilder($cplAccountGroupBuilderClass)
            ->disableOriginalConstructor()
            ->getMock();

        $cplAccountBuilderClass = 'OroB2B\Bundle\PricingBundle\Builder\AccountCombinedPriceListsBuilder';
        $this->cplAccountBuilder = $this->getMockBuilder($cplAccountBuilderClass)
            ->disableOriginalConstructor()
            ->getMock();

        $collectionChangesClass = 'OroB2B\Bundle\PricingBundle\Entity\ChangedPriceListCollection';
        $collectionChangesRepositoryClass = 'OroB2B\Bundle\PricingBundle\Entity\Repository\ChangedPriceListCollectionRepository';

        $this->collectionChangesRepository = $this->getMockBuilder($collectionChangesRepositoryClass)
            ->disableOriginalConstructor()
            ->getMock();

        $combinedPriceListEm = $this->getMock('\Doctrine\Common\Persistence\ObjectManager');
        $combinedPriceListEm->expects($this->any())
            ->method('getRepository')
            ->with($collectionChangesClass)
            ->will($this->returnValue($this->collectionChangesRepository));

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->will(
                $this->returnValueMap(
                    [
                        [$collectionChangesClass, $combinedPriceListEm],
                    ]
                )
            );

        $this->consumer = new CombinedPriceListQueueConsumer(
            $this->registry,
            $this->cplBuilder,
            $this->cplWebsiteBuilder,
            $this->cplAccountGroupBuilder,
            $this->cplAccountBuilder
        );
    }


    /**
     * @dataProvider processDataProvider
     * @param $assertBuilders
     * @param $repositoryData
     */
    public function testProcess($assertBuilders, $repositoryData)
    {
        $this->assertRebuild($assertBuilders, $repositoryData);
        $this->consumer->process();
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Account $account */
        $account = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Account');

        /** @var \PHPUnit_Framework_MockObject_MockObject|AccountGroup $accountGroup */
        $accountGroup = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountGroup');

        /** @var \PHPUnit_Framework_MockObject_MockObject|Website $website */
        $website = $this->getMock('OroB2B\Bundle\WebsiteBundle\Entity\Website');

        return [
            'full queue' => [
                'assertBuilders' => [
                    'cplBuilder' => $this->once(),
                    'cplWebsiteBuilder' => $this->once(),
                    'cplAccountGroupBuilder' => $this->once(),
                    'cplAccountBuilder' => $this->once(),
                ],
                'repositoryData' => [
                    [
                        'getAccount' => [
                            'data' => $account,
                            'expects' => $this->exactly(2),
                        ],
                        'getAccountGroup' => [
                            'data' => null,
                            'expects' => $this->never(),
                        ],
                        'getWebsite' => [
                            'data' => $website,
                            'expects' => $this->once(),
                        ],
                    ],
                    [
                        'getAccount' => [
                            'data' => null,
                            'expects' => $this->once(),
                        ],
                        'getAccountGroup' => [
                            'data' => $accountGroup,
                            'expects' => $this->exactly(2),
                        ],
                        'getWebsite' => [
                            'data' => $website,
                            'expects' => $this->once(),
                        ],
                    ],
                    [
                        'getAccount' => [
                            'data' => null,
                            'expects' => $this->once(),
                        ],
                        'getAccountGroup' => [
                            'data' => null,
                            'expects' => $this->once(),
                        ],
                        'getWebsite' => [
                            'data' => $website,
                            'expects' => $this->exactly(2),
                        ],
                    ],
                    [
                        'getAccount' => [
                            'data' => null,
                            'expects' => $this->once(),
                        ],
                        'getAccountGroup' => [
                            'data' => null,
                            'expects' => $this->once(),
                        ],
                        'getWebsite' => [
                            'data' => null,
                            'expects' => $this->once(),
                        ],
                    ],
                ],
            ],
            'empty queue' => [
                'assertBuilders' => [
                    'cplBuilder' => $this->never(),
                    'cplWebsiteBuilder' => $this->never(),
                    'cplAccountGroupBuilder' => $this->never(),
                    'cplAccountBuilder' => $this->never(),
                ],
                'repositoryData' => [],
            ],
        ];
    }

    /**
     * @param $assertBuilders
     * @param $repositoryData
     */
    protected function assertRebuild($assertBuilders, $repositoryData)
    {
        $this->collectionChangesRepository->expects($this->once())
            ->method('getCollectionChangesIterator')
            ->willReturn($this->getCollectionChangesMock($repositoryData));

        $this->cplBuilder->expects($assertBuilders['cplBuilder'])
            ->method('build');

        $this->cplWebsiteBuilder->expects($assertBuilders['cplWebsiteBuilder'])
            ->method('build');

        $this->cplAccountGroupBuilder->expects($assertBuilders['cplAccountGroupBuilder'])
            ->method('build');

        $this->cplAccountBuilder->expects($assertBuilders['cplAccountBuilder'])
            ->method('build');
    }

    /**
     * @param array $changesData
     * @return array
     */
    protected function getCollectionChangesMock(array $changesData)
    {
        $changes = [];
        foreach ($changesData as $changeData) {
            $collectionChange = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\ChangedPriceListCollection');
            foreach ($changeData as $method => $data) {
                $collectionChange->expects($data['expects'])
                    ->method($method)
                    ->willReturn($data['data']);
            }
            $changes[] = $collectionChange;
        }

        return $changes;
    }
}
