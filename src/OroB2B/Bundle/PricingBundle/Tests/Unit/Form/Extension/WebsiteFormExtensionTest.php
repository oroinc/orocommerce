<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;

use OroB2B\Bundle\PricingBundle\Form\Extension\WebsiteFormExtension;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteType;

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

    public function setUp()
    {
        parent::setUp();
        $this->priceLists = $this->createPriceLists(2);
    }

    public function testBuild()
    {
        /** @var \Symfony\Bridge\Doctrine\RegistryInterface $registry */
        $registry = $this->getMockBuilder('Symfony\Bridge\Doctrine\RegistryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $extension = new WebsiteFormExtension($registry, self::PRICE_LIST_TO_WEBSITE_CLASS);

        /** @var \Symfony\Component\Form\Test\FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('add')
            ->with(
                WebsiteFormExtension::PRICE_LISTS_TO_WEBSITE_FIELD,
                PriceListCollectionType::NAME,
                [
                    'allow_add_after' => false,
                    'allow_add' => true,
                    'required' => false
                ]
            );

        $builder->expects($this->exactly(2))
            ->method('addEventListener');

        $builder->expects($this->at(1))
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, [$extension, 'onPostSetData']);
        $builder->expects($this->at(2))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$extension, 'onPostSubmit'], 10);

        $extension->buildForm($builder, []);
    }

    public function testOnPostSetDataWebsiteNotExists()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Bridge\Doctrine\RegistryInterface $registry */
        $registry = $this->getMockBuilder('Symfony\Bridge\Doctrine\RegistryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $extension = new WebsiteFormExtension($registry, self::PRICE_LIST_TO_WEBSITE_CLASS);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->never())->method('get');
        $event = new FormEvent($form, []);

        $extension->onPostSetData($event);
    }

    public function testOnPostSetData()
    {
        $priceListFrom = $this->getFormMock();
        $priceListFrom->expects($this->once())
            ->method('setData')
            ->with([
                ['priceList' => $this->getExisting()[1]->getPriceList(), 'priority' => 100, 'merge' => true],
                ['priceList' => $this->getExisting()[2]->getPriceList(), 'priority' => 200, 'merge' => true],
            ]);

        $rootForm = $this->getFormMock();
        $rootForm->expects($this->once())->method('get')->willReturn($priceListFrom);

        $event = new FormEvent($rootForm, $this->createWebsite());
        $extension = $this->createExtension();
        $extension->onPostSetData($event);
    }

    public function testOnPostSubmitDeleted()
    {
        $priceListFrom = $this->getMock('Symfony\Component\Form\FormInterface');
        $priceListFrom->expects($this->once())
            ->method('getData')
            ->willReturn([
                ['priceList' => $this->getExisting()[1]->getPriceList(), 'priority' => 100, 'merge' => true],
                ['priceList' => null, 'priority' => 200, 'merge' => true],
            ]);

        $rootForm = $this->getFormMock();
        $rootForm->expects($this->once())->method('get')->willReturn($priceListFrom);
        $rootForm->expects($this->once())->method('isValid')->willReturn(true);

        $this->getManagerMock()
            ->expects($this->once())
            ->method('remove')
            ->with($this->getExisting()[2]);

        $event = new FormEvent($rootForm, $this->createWebsite());
        $extension = $this->createExtension();
        $extension->onPostSubmit($event);
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
        $extension = new WebsiteFormExtension($this->getRegistryMock(), self::PRICE_LIST_TO_WEBSITE_CLASS);
        $extension->onPostSubmit($event);
    }

    public function testOnPostSubmitNew()
    {
        $priceListFrom = $this->getMock('Symfony\Component\Form\FormInterface');
        $website = $this->createWebsite();

        /** @var PriceList $addedPriceList */
        $addedPriceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 3);
        $expected = new PriceListToWebsite();
        $expected->setPriceList($addedPriceList)
            ->setPriority(300)
            ->setWebsite($website);
        $priceListFrom->expects($this->once())
            ->method('getData')
            ->willReturn([
                ['priceList' => $this->getExisting()[1]->getPriceList(), 'priority' => 100, 'merge' => true],
                ['priceList' => $this->getExisting()[2]->getPriceList(), 'priority' => 200, 'merge' => true],
                ['priceList' => $addedPriceList, 'priority' => 300, 'merge' => true],
                ['priceList' => null, 'priority' => 400, 'merge' => false]
            ]);

        $rootForm = $this->getFormMock();
        $rootForm->expects($this->once())->method('get')->willReturn($priceListFrom);
        $rootForm->expects($this->once())->method('isValid')->willReturn(true);

        $this->getManagerMock()
            ->expects($this->once())
            ->method('persist')
            ->with($expected);

        $event = new FormEvent($rootForm, $website);
        $extension = $this->createExtension();
        $extension->onPostSubmit($event);
    }

    public function testGetExtendedType()
    {
        $exception = new WebsiteFormExtension($this->getRegistryMock(), self::PRICE_LIST_TO_WEBSITE_CLASS);
        $this->assertSame(WebsiteType::NAME, $exception->getExtendedType());
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
        $registry = $this->getRegistryMock();

        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::PRICE_LIST_TO_WEBSITE_CLASS)
            ->willReturn($this->getManagerMock());

        return new WebsiteFormExtension($registry, self::PRICE_LIST_TO_WEBSITE_CLASS);
    }

    /**
     * @return \Symfony\Bridge\Doctrine\RegistryInterface|\PHPUnit_Framework_MockObject_MockObject $registry
     */
    protected function getRegistryMock()
    {
        return $this->getMockBuilder('Symfony\Bridge\Doctrine\RegistryInterface')
            ->disableOriginalConstructor()
            ->getMock();
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

            $manager->expects($this->once())
                ->method('getRepository')
                ->with(self::PRICE_LIST_TO_WEBSITE_CLASS)
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

            $repository->expects($this->once())
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
}
