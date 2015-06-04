<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Extension\AbstractPriceListExtension;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;

abstract class AbstractPriceListExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);
    }

    protected function tearDown()
    {
        unset($this->registry, $this->repository);
    }

    public function testBuildForm()
    {
        /** @var AbstractPriceListExtension $extension */
        $extension = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Form\Extension\AbstractPriceListExtension')
            ->disableOriginalConstructor()
            ->setMethods(['getExtendedType', 'onPostSetData', 'onPostSubmit'])
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'priceList',
                PriceListSelectType::NAME,
                [
                    'label' => 'orob2b.pricing.pricelist.entity_label',
                    'required' => false,
                    'mapped' => false,
                ]
            );
        $builder->expects($this->exactly(2))
            ->method('addEventListener');
        $builder->expects($this->at(1))
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, [$extension, 'onPostSetData']);
        $builder->expects($this->at(2))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$extension, 'onPostSubmit']);

        $extension->buildForm($builder, []);
    }

    /**
     * @param mixed $formData
     * @param bool $expects
     * @param PriceList|null $priceList
     *
     * @dataProvider onPostSetDataProvider
     */
    public function testOnPostSetData($formData, $expects, $priceList = null)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormEvent $event */
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())->method('getData')->willReturn($formData);

        if ($expects) {
            $this->repository->expects($this->once())
                ->method($this->getGetterMethodName())
                ->with($formData)
                ->willReturn($priceList);

            $priceListFrom = $this->getMock('Symfony\Component\Form\FormInterface');
            $priceListFrom->expects($this->once())->method('setData')->with($priceList);
            $rootForm = $this->getMock('Symfony\Component\Form\FormInterface');
            $rootForm->expects($this->once())->method('get')->willReturn($priceListFrom);

            $event->expects($this->once())->method('getForm')->willReturn($rootForm);
        }

        $this->getExtension()->onPostSetData($event);
    }

    /**
     * @param mixed $formData
     * @param bool $expects
     * @param bool $isFormValid
     * @param PriceList|null $priceList
     *
     * @dataProvider onPostSubmitDataProvider
     */
    public function testOnPostSubmit($formData, $expects, $isFormValid = true, $priceList = null)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormEvent $event */
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())->method('getData')->willReturn($formData);

        $rootForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $rootForm->expects($this->any())->method('isValid')->willReturn($isFormValid);
        $event->expects($this->any())->method('getForm')->willReturn($rootForm);

        if ($expects && $isFormValid) {
            $this->repository->expects($this->once())
                ->method($this->getSetterMethodName())
                ->with($formData, $priceList);

            $priceListFrom = $this->getMock('Symfony\Component\Form\FormInterface');
            $priceListFrom->expects($this->once())->method('getData')->willReturn($priceList);

            $rootForm->expects($this->once())->method('get')->willReturn($priceListFrom);
        }

        $this->getExtension()->onPostSubmit($event);
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
     * @return array
     */
    abstract public function onPostSetDataProvider();

    /**
     * @return array
     */
    abstract public function onPostSubmitDataProvider();

    /**
     * @return AbstractPriceListExtension
     */
    abstract protected function getExtension();

    /**
     * @return string
     */
    abstract protected function getGetterMethodName();

    /**
     * @return string
     */
    abstract protected function getSetterMethodName();

    /**
     * getExtendedType method test case
     */
    abstract public function testGetExtendedType();
}
