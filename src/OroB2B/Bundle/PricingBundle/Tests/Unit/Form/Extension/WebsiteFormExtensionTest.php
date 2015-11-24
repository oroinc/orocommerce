<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use OroB2B\Bundle\PricingBundle\Validator\Constraints\UniquePriceList;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\PricingBundle\Form\Extension\WebsiteFormExtension;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

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
        foreach ($this->priceLists as $priceList) {
            $priceListToWebsite = new PriceListToWebsite();
            $priceListToWebsite->setPriceList($priceList)
                ->setPriority($priceList->getId() * 100);
            $this->existing[$priceList->getId()] = $priceListToWebsite;
        }
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
                CollectionType::NAME,
                [
                    'label' => false,
                    'type' => PriceListSelectWithPriorityType::NAME,
                    'options' => [
                        'error_bubbling' => false,
                    ],
                    'handle_primary' => false,
                    'allow_add_after' => false,
                    'allow_add' => true,
                    'error_bubbling' => false,
                    'attr' => [
                        'class' => 'price_lists_collection'
                    ],
                    'constraints' => [
                        new UniquePriceList()
                    ],
                    'mapped' => false,
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

    public function testWebsiteNotExists()
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
        $priceListFrom = $this->getMock('Symfony\Component\Form\FormInterface');
        $priceListFrom->expects($this->once())
            ->method('setData')
            ->with([
                ['priceList' => $this->existing[1]->getPriceList(), 'priority'=>100],
                ['priceList' => $this->existing[2]->getPriceList(), 'priority'=>200]
            ]);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $rootForm */
        $rootForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $rootForm->expects($this->once())->method('get')->willReturn($priceListFrom);

        $event = new FormEvent($rootForm, $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', 1));
        $extension = $this->createExtension();
        $extension->onPostSetData($event);
    }

    public function testOnPostSubmitDeleted()
    {
        $priceListFrom = $this->getMock('Symfony\Component\Form\FormInterface');
        $priceListFrom->expects($this->once())
            ->method('getData')
            ->willReturn([
                ['priceList' => $this->existing[1]->getPriceList(), 'priority'=>100]
            ]);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $rootForm */
        $rootForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $rootForm->expects($this->once())->method('get')->willReturn($priceListFrom);
        $rootForm->expects($this->once())->method('isValid')->willReturn(true);

        $this->getManagerMock()
            ->expects($this->once())
            ->method('remove')
            ->with($this->existing[2]);

        $event = new FormEvent($rootForm, $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', 1));
        $extension = $this->createExtension();
        $extension->onPostSubmit($event);
    }

    public function testOnPostSubmitNew()
    {
        $priceListFrom = $this->getMock('Symfony\Component\Form\FormInterface');
        /** @var Website $website */
        $website = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', 1);
        /** @var PriceList $addedPriceList */
        $addedPriceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 3);
        $expected = new PriceListToWebsite();
        $expected->setPriceList($addedPriceList)
            ->setPriority(300)
            ->setWebsite($website);
        $priceListFrom->expects($this->once())
            ->method('getData')
            ->willReturn([
                ['priceList' => $this->existing[1]->getPriceList(), 'priority'=>100],
                ['priceList' => $this->existing[2]->getPriceList(), 'priority'=>200],
                ['priceList' => $addedPriceList, 'priority'=>300]
            ]);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $rootForm */
        $rootForm = $this->getMock('Symfony\Component\Form\FormInterface');
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

    /**
     * @return WebsiteFormExtension
     */
    protected function createExtension()
    {
        /** @var \Symfony\Bridge\Doctrine\RegistryInterface|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder('Symfony\Bridge\Doctrine\RegistryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::PRICE_LIST_TO_WEBSITE_CLASS)
            ->willReturn($this->getManagerMock());

        return new WebsiteFormExtension($registry, self::PRICE_LIST_TO_WEBSITE_CLASS);
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
     * @param $count
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
                ->willReturn($this->existing);
            $this->repositoryMock = $repository;
        }

        return $this->repositoryMock;
    }
}
