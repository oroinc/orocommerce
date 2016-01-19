<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;

use OroB2B\Bundle\PricingBundle\Event\PriceListCollectionChange;
use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use OroB2B\Bundle\PricingBundle\Form\Extension\WebsiteFormExtension;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteType;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class WebsiteFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    const PRICE_LIST_TO_WEBSITE_CLASS = '\PriceListToWebsite';

    /** @var  PriceList[] */
    protected $priceLists = [];

    /** @var  PriceListToWebsite[] */
    protected $existing = [];

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $repositoryMock;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $managerMock;

    /** @var  EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var  WebsiteFormExtension */
    protected $extension;

    public function setUp()
    {
        parent::setUp();
        $this->priceLists = $this->createPriceLists(2);
        /** @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder('Symfony\Bridge\Doctrine\RegistryInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->extension = new WebsiteFormExtension(
            $registry,
            self::PRICE_LIST_TO_WEBSITE_CLASS,
            $this->eventDispatcher
        );

    }

    public function testBuild()
    {
        /** @var \Symfony\Component\Form\Test\FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                WebsiteFormExtension::PRICE_LISTS_TO_WEBSITE_FIELD,
                PriceListCollectionType::NAME,
                [
                    'allow_add_after' => false,
                    'allow_add' => true,
                    'required' => false,
                ]
            )
            ->willReturn($builder);
        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'fallback',
                'choice',
                [
                    'label' => 'orob2b.pricing.fallback.label',
                    'mapped' => false,
                    'choices' => [
                        PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY =>
                            'orob2b.pricing.fallback.current_website_only.label',
                        PriceListWebsiteFallback::CONFIG =>
                            'orob2b.pricing.fallback.config.label',
                    ],
                ]
            );
        $builder->expects($this->exactly(2))
            ->method('addEventListener');

        $builder->expects($this->at(2))
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, [$this->extension, 'onPostSetData']);
        $builder->expects($this->at(3))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->extension, 'onPostSubmit'], 10);

        $this->extension->buildForm($builder, []);
    }

    public function testOnPostSetDataWebsiteNotExists()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->never())->method('get');
        $event = new FormEvent($form, []);

        $this->extension->onPostSetData($event);
    }

    /**
     * @dataProvider onPostSetDataDataProvider
     *
     * @param PriceListWebsiteFallback|null $fallbackEntity
     * @param integer $expectedFallbackValue
     */
    public function testOnPostSetData($fallbackEntity, $expectedFallbackValue)
    {
        $priceListFrom = $this->getFormMock();
        $priceListFrom->expects($this->once())
            ->method('setData')
            ->with(
                [
                    ['priceList' => $this->getExisting()[1]->getPriceList(), 'priority' => 100, 'mergeAllowed' => true],
                    ['priceList' => $this->getExisting()[2]->getPriceList(), 'priority' => 200, 'mergeAllowed' => true],
                ]
            );

        $rootForm = $this->getFormMock();
        $fallbackField = $this->getFormMock();
        $fallbackField->expects($this->once())->method('setData')->with($expectedFallbackValue);
        $rootForm->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['priceList', $priceListFrom],
                        ['fallback', $fallbackField],
                    ]
                )
            );

        $this->getRepositoryMock()->expects($this->at(1))->method('findOneBy')->willReturn($fallbackEntity);
        $event = new FormEvent($rootForm, $this->createWebsite());
        $extension = $this->createExtension();
        $extension->onPostSetData($event);
    }

    /**
     * @return array
     */
    public function onPostSetDataDataProvider()
    {
        return [
            'notExistingFallback' => [
                'fallbackEntity' => null,
                'expectedFallbackValue' => PriceListWebsiteFallback::CONFIG,
            ],
            'existingFallback' => [
                'fallbackEntity' => new PriceListWebsiteFallback(),
                'expectedFallbackValue' => PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY,
            ],
            'existingDefaultFallback' => [
                'fallbackEntity' => (new PriceListWebsiteFallback())
                    ->setFallback(PriceListWebsiteFallback::CONFIG),
                'expectedFallbackValue' => PriceListWebsiteFallback::CONFIG,
            ],
        ];
    }

    public function testOnPostSubmitFormInvalid()
    {
        $rootForm = $this->getFormMock();
        $rootForm->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $rootForm->expects($this->never())
            ->method('get');

        $event = new FormEvent($rootForm, $this->createWebsite());
        $this->extension->onPostSubmit($event);
    }

    /**
     * @dataProvider testOnPostSubmitDataProvider
     * @param array $submittedData
     * @param boolean $expectedDispatch
     */
    public function testOnPostSubmit($submittedData, $expectedDispatch)
    {
        $priceListFrom = $this->getMock('Symfony\Component\Form\FormInterface');
        $website = $this->createWebsite();

        // get deleted relations
        $deletedPriceListRelations = $this->getRemovedPriceLists($submittedData['priceLists']);

        // get new submitted relations
        list($submittedData['priceLists'], $newPriceListRelations) = $this->createNewPriceLists(
            $submittedData['priceLists'],
            $website
        );
        $priceListFrom->expects($this->once())
            ->method('getData')
            ->willReturn($submittedData['priceLists']);
        $fallbackForm = $this->getFormMock();
        $fallbackForm->expects($this->once())
            ->method('getData')
            ->willReturn($submittedData['fallback']);
        $rootForm = $this->getFormMock();
        $rootForm->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['priceList', $priceListFrom],
                        ['fallback', $fallbackForm],
                    ]
                )
            );
        $fallback = (new PriceListWebsiteFallback())->setFallback(PriceListWebsiteFallback::CONFIG);
        $this->getRepositoryMock()->expects($this->at(1))->method('findOneBy')->willReturn($fallback);
        $rootForm->expects($this->once())->method('isValid')->willReturn(true);
        // for new created submitted relations
        if ($newPriceListRelations) {
            $this->getManagerMock()
                ->expects($this->exactly(count($newPriceListRelations)))
                ->method('persist')
                ->willReturnCallback(
                    function ($entity) use ($newPriceListRelations) {
                        $this->assertTrue(in_array($entity, $newPriceListRelations));
                    }
                );
        }
        // for deleted relations
        if ($deletedPriceListRelations) {
            $this->getManagerMock()
                ->expects($this->exactly(count($deletedPriceListRelations)))
                ->method('remove')
                ->willReturnCallback(
                    function ($entity) use ($deletedPriceListRelations) {
                        $this->assertTrue(in_array($entity, $deletedPriceListRelations));
                    }
                );
        }
        $event = new FormEvent($rootForm, $website);
        if ($expectedDispatch) {
            $this->eventDispatcher
                ->expects($this->once())
                ->method('dispatch')
                ->with(PriceListCollectionChange::BEFORE_CHANGE, new PriceListCollectionChange($website));
        } else {
            $this->eventDispatcher
                ->expects($this->never())
                ->method('dispatch');
        }
        $extension = $this->createExtension();
        $extension->onPostSubmit($event);
    }

    public function testOnPostSubmitDataProvider()
    {
        return [
            'sameData' => [
                'submittedData' => [
                    'fallback' => PriceListWebsiteFallback::CONFIG,
                    'priceLists' => [
                        ['priceList' => 1, 'priority' => 100, 'mergeAllowed' => true],
                        ['priceList' => 2, 'priority' => 200, 'mergeAllowed' => true]
                    ]
                ],
                'expectDispatch' => false
            ],
            'fallbackChangeOnly' => [
                'submittedData' => [
                    'fallback' => PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY,
                    'priceLists' => [
                        ['priceList' => 1, 'priority' => 100, 'mergeAllowed' => true],
                        ['priceList' => 2, 'priority' => 200, 'mergeAllowed' => true]
                    ]
                ],
                'expectDispatch' => true
            ],
            'addNew' => [
                'submittedData' => [
                    'fallback' => PriceListWebsiteFallback::CONFIG,
                    'priceLists' => [
                        ['priceList' => 1, 'priority' => 100, 'mergeAllowed' => true],
                        ['priceList' => 2, 'priority' => 200, 'mergeAllowed' => true],
                        ['priceList' => 'new', 'priority' => 22, 'mergeAllowed' => false],
                        ['priceList' => 'new', 'priority' => 1, 'mergeAllowed' => true]
                    ]
                ],
                'expectDispatch' => true
            ],
            'remove' => [
                'submittedData' => [
                    'fallback' => PriceListWebsiteFallback::CONFIG,
                    'priceLists' => [
                        ['priceList' => 1, 'priority' => 100, 'mergeAllowed' => true],
                    ]
                ],
                'expectDispatch' => true
            ],
            'update' => [
                'submittedData' => [
                    'fallback' => PriceListWebsiteFallback::CONFIG,
                    'priceLists' => [
                        ['priceList' => 1, 'priority' => 100, 'mergeAllowed' => true],
                        ['priceList' => 2, 'priority' => 100, 'mergeAllowed' => true]
                    ]
                ],
                'expectDispatch' => true
            ],
            'updateAndRemove' => [
                'submittedData' => [
                    'fallback' => PriceListWebsiteFallback::CONFIG,
                    'priceLists' => [
                        ['priceList' => 2, 'priority' => 100, 'mergeAllowed' => true],
                    ]
                ],
                'expectDispatch' => true
            ],
            'updateAndCreate' => [
                'submittedData' => [
                    'fallback' => PriceListWebsiteFallback::CONFIG,
                    'priceLists' => [
                        ['priceList' => 2, 'priority' => 100, 'mergeAllowed' => true],
                        ['priceList' => 'new', 'priority' => 100, 'mergeAllowed' => false],
                    ]
                ],
                'expectDispatch' => true
            ],
            'updateAndCreateAndRemove' => [
                'submittedData' => [
                    'fallback' => PriceListWebsiteFallback::CONFIG,
                    'priceLists' => [
                        ['priceList' => 1, 'priority' => 100, 'mergeAllowed' => false],
                        ['priceList' => 'new', 'priority' => 12, 'mergeAllowed' => true],
                    ]
                ],
                'expectDispatch' => true
            ],
        ];
    }

    /**
     * @param array $submitted
     * @param Website $website
     * @return array
     */
    protected function createNewPriceLists(array $submitted, Website $website)
    {
        $i = 3;
        $newPriceListRelations = [];
        foreach ($submitted as &$priceList) {
            if ($priceList['priceList'] === 'new') {
                /** @var PriceList $priceListEntity */
                $priceListEntity = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', $i++);
                $priceList['priceList'] = $priceListEntity;
                $priceListRelation = new PriceListToWebsite();
                $priceListRelation->setPriority($priceList['priority'])
                    ->setWebsite($website)
                    ->setPriceList($priceListEntity)
                    ->setMergeAllowed($priceList['mergeAllowed']);
                $newPriceListRelations[] = $priceListRelation;
            } else {
                $priceList['priceList'] = $this->getExisting()[$priceList['priceList']]->getPriceList();
            }
        }


        return [$submitted, $newPriceListRelations];
    }

    /**
     * @param array $submitted
     * @return PriceListToWebsite[]
     */
    protected function getRemovedPriceLists(array $submitted)
    {
        $deletedRelations = [];
        foreach ($this->getExisting() as $key => $existPriceListRelation) {
            $deleted = true;
            foreach ($submitted as $submittedRelation) {
                if ($submittedRelation['priceList'] === $key) {
                    $deleted = false;
                    break;
                }
            }
            if ($deleted) {
                $deletedRelations[] = $existPriceListRelation;
            }
        }

        return $deletedRelations;
    }

    public function testGetExtendedType()
    {
        $this->assertSame(WebsiteType::NAME, $this->extension->getExtendedType());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected function getFormMock()
    {
        return $this->getMock('Symfony\Component\Form\FormInterface');
    }

    /**
     * @return Website
     */
    protected function createWebsite()
    {
        return $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', 1);
    }

    /**
     * @return WebsiteFormExtension
     */
    protected function createExtension()
    {
        /** @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder('Symfony\Bridge\Doctrine\RegistryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->getManagerMock());

        return new WebsiteFormExtension($registry, self::PRICE_LIST_TO_WEBSITE_CLASS, $this->getEventDispatcher());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getManagerMock()
    {
        if (!$this->managerMock) {
            $manager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                ->disableOriginalConstructor()
                ->getMock();

            $manager->expects($this->any())
                ->method('getRepository')
                ->willReturn($this->getRepositoryMock());

            $this->managerMock = $manager;
        }

        return $this->managerMock;
    }

    /**
     * @param int $count
     * @return array
     */
    protected function createPriceLists($count)
    {
        $priceLists = [];
        for ($i = 1; $i <= $count; $i++) {
            $priceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', $i);
            $priceList->setName("Price List $i");
            $priceLists[] = $priceList;
        }

        return $priceLists;
    }

    /**
     * @param string $className
     * @param int $id
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRepositoryMock()
    {
        if (!$this->repositoryMock) {
            $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
                ->disableOriginalConstructor()
                ->getMock();

            $repository->expects($this->at(0))
                ->method('findBy')
                ->willReturn($this->getExisting());
            $this->repositoryMock = $repository;
        }

        return $this->repositoryMock;
    }

    /**
     * @return \OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite[]
     */
    protected function getExisting()
    {
        if (!$this->existing) {
            foreach ($this->priceLists as $priceList) {
                $priceListToWebsite = new PriceListToWebsite();
                $priceListToWebsite->setPriceList($priceList)
                    ->setPriority($priceList->getId() * 100);
                $this->existing[$priceList->getId()] = $priceListToWebsite;
            }
        }

        return $this->existing;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected function getEventDispatcher()
    {
        if (!$this->eventDispatcher) {
            $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        }

        return $this->eventDispatcher;
    }
}
