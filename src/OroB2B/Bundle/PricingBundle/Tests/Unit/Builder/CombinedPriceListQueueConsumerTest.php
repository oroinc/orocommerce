<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\AccountCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Entity\PriceListChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListChangeTriggerRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\AccountCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\AccountGroupCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\WebsiteCPLUpdateEvent;

class CombinedPriceListQueueConsumerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListChangeTriggerRepository
     */
    protected $priceListChangeTriggerRepository;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CombinedPriceListsBuilder
     */
    protected $cplBuilder;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WebsiteCombinedPriceListsBuilder
     */
    protected $cplWebsiteBuilder;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AccountGroupCombinedPriceListsBuilder
     */
    protected $cplAccountGroupBuilder;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AccountCombinedPriceListsBuilder
     */
    protected $cplAccountBuilder;
    /**
     * @var CombinedPriceListQueueConsumer
     */
    protected $consumer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected $dispatcher;

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

        $collectionChangesClass = 'OroB2B\Bundle\PricingBundle\Entity\PriceListChangeTrigger';
        $plChangeRepositoryClass = 'OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListChangeTriggerRepository';

        $this->priceListChangeTriggerRepository = $this->getMockBuilder($plChangeRepositoryClass)
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = $this->getMock('\Doctrine\Common\Persistence\ObjectManager');

        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with($collectionChangesClass)
            ->will($this->returnValue($this->priceListChangeTriggerRepository));

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->will(
                $this->returnValueMap(
                    [
                        [$collectionChangesClass, $this->manager],
                    ]
                )
            );

        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->consumer = new CombinedPriceListQueueConsumer(
            $this->registry,
            $this->cplBuilder,
            $this->cplWebsiteBuilder,
            $this->cplAccountGroupBuilder,
            $this->cplAccountBuilder,
            $this->dispatcher
        );
    }

    /**
     * @dataProvider processDataProvider
     * @param $assertBuilders
     * @param $assertManager
     * @param $repositoryData
     */
    public function testProcess($assertBuilders, $assertManager, $repositoryData)
    {
        $this->assertRebuild($assertBuilders, $repositoryData);
        $this->assertManager($assertManager);
        $this->consumer->process();
    }

    public function testProcessDataForceRebuild()
    {
        $forceTrigger = new PriceListChangeTrigger();
        $forceTrigger->setForce(true);

        $this->priceListChangeTriggerRepository
            ->expects($this->once())
            ->method('findBuildAllForceTrigger')
            ->willReturn($forceTrigger);

        $this->cplBuilder->expects($this->once())
            ->method('build')
            ->with(true);

        $this->priceListChangeTriggerRepository
            ->expects($this->once())
            ->method('deleteAll');

        $this->priceListChangeTriggerRepository
            ->expects($this->never())
            ->method('getPriceListChangeTriggersIterator');

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
                'assertManager' => [
                    'remove' => 4,
                    'flush' => 1
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
                'assertManager' => [
                    'remove' => 0,
                    'flush' => 1
                ],
                'repositoryData' => [],
            ],
        ];
    }

    /**
     * @dataProvider dispatchAccountScopeEventDataProvider
     * @param array $builtList
     */
    public function testDispatchAccountScopeEvent(array $builtList)
    {
        $this->priceListChangeTriggerRepository->expects($this->any())
            ->method('getPriceListChangeTriggersIterator')
            ->willReturn($this->getCollectionChangesMock([]));

        $this->cplAccountBuilder->expects($this->once())
            ->method('getBuiltList')
            ->willReturn($builtList);
        if (isset($builtList['account'])) {
            $this->dispatcher->expects($this->once())
                ->method('dispatch')
                ->with(AccountCPLUpdateEvent::NAME);
        } else {
            $this->dispatcher->expects($this->never())
                ->method('dispatch');
        }

        $this->consumer->process();
    }

    /**
     * @return array
     */
    public function dispatchAccountScopeEventDataProvider()
    {
        return [
            'with account scope' => [
                'builtList' => [
                    'account' => [
                        1 => [
                            1 => true,
                            2 => true
                        ]
                    ]
                ]
            ],
            'without account scope' => [
                'builtList' => []
            ],
        ];
    }

    /**
     * @dataProvider dispatchAccountGroupScopeEventDataProvider
     * @param array $builtList
     */
    public function testDispatchAccountGroupScopeEvent(array $builtList)
    {
        $this->priceListChangeTriggerRepository->expects($this->any())
            ->method('getPriceListChangeTriggersIterator')
            ->willReturn($this->getCollectionChangesMock([]));

        $this->cplAccountGroupBuilder->expects($this->once())
            ->method('getBuiltList')
            ->willReturn($builtList);
        if ($builtList) {
            $this->dispatcher->expects($this->once())
                ->method('dispatch')
                ->with(AccountGroupCPLUpdateEvent::NAME);
        } else {
            $this->dispatcher->expects($this->never())
                ->method('dispatch');
        }

        $this->consumer->process();
    }

    /**
     * @return array
     */
    public function dispatchAccountGroupScopeEventDataProvider()
    {
        return [
            'with account group scope' => [
                'builtList' => [
                    1 => [
                        1 => true,
                        2 => true
                    ]
                ]
            ],
            'without account group scope' => [
                'builtList' => []
            ],
        ];
    }

    /**
     * @dataProvider dispatchWebsiteScopeEventDataProvider
     * @param array $builtList
     */
    public function testDispatchWebsiteScopeEvent(array $builtList)
    {
        $this->priceListChangeTriggerRepository->expects($this->any())
            ->method('getPriceListChangeTriggersIterator')
            ->willReturn($this->getCollectionChangesMock([]));

        $this->cplWebsiteBuilder->expects($this->once())
            ->method('getBuiltList')
            ->willReturn($builtList);

        if ($builtList) {
            $this->dispatcher->expects($this->once())
                ->method('dispatch')
                ->with(WebsiteCPLUpdateEvent::NAME);
        } else {
            $this->dispatcher->expects($this->never())
                ->method('dispatch');
        }

        $this->consumer->process();
    }

    /**
     * @return array
     */
    public function dispatchWebsiteScopeEventDataProvider()
    {
        return [
            'with account group scope' => [
                'builtList' => [1, 2, 3]
            ],
            'without account group scope' => [
                'builtList' => []
            ],
        ];
    }

    /**
     * @dataProvider dispatchConfigScopeEventDataProvider
     * @param bool $isBuilt
     */
    public function testDispatchConfigScopeEvent($isBuilt)
    {
        $this->priceListChangeTriggerRepository->expects($this->any())
            ->method('getPriceListChangeTriggersIterator')
            ->willReturn($this->getCollectionChangesMock([]));

        $this->cplBuilder->expects($this->once())
            ->method('isBuilt')
            ->willReturn($isBuilt);

        if ($isBuilt) {
            $this->dispatcher->expects($this->once())
                ->method('dispatch')
                ->with(ConfigCPLUpdateEvent::NAME);
        } else {
            $this->dispatcher->expects($this->never())
                ->method('dispatch');
        }

        $this->consumer->process();
    }

    /**
     * @return array
     */
    public function dispatchConfigScopeEventDataProvider()
    {
        return [
            'built' => [
                '$isBuilt' => true
            ],
            'not built' => [
                'builtList' => false
            ],
        ];
    }

    /**
     * @param $assertBuilders
     * @param $repositoryData
     */
    protected function assertRebuild($assertBuilders, $repositoryData)
    {
        $this->priceListChangeTriggerRepository->expects($this->once())
            ->method('getPriceListChangeTriggersIterator')
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
            $collectionChange = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\PriceListChangeTrigger');
            foreach ($changeData as $method => $data) {
                $collectionChange->expects($data['expects'])
                    ->method($method)
                    ->willReturn($data['data']);
            }
            $changes[] = $collectionChange;
        }

        return $changes;
    }

    /**
     * @param array $asserts
     */
    protected function assertManager(array $asserts)
    {
        $this->manager->expects($this->exactly($asserts['remove']))
            ->method('remove');
        $this->manager->expects($this->exactly($asserts['flush']))
            ->method('flush');
    }
}
