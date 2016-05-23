<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Extend\Entity\EX_OroB2BCatalogBundle_Category;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\SEOBundle\Form\Extension\ProductFormExtension;
use OroB2B\Bundle\SEOBundle\Tests\Unit\Entity\Stub\ProductStub;

class ProductFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var  OroEntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var  ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    public function setUp()
    {
        $this->manager = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\OroEntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testOnPostSubmitPersistsMetaObjects()
    {
        $this->registry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroB2BFallbackBundle:LocalizedFallbackValue')
            ->willReturn($this->manager);
        $categoryExtension = new ProductFormExtension($this->registry);
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $category = new ProductStub();
        $category->addMetaTitles(new LocalizedFallbackValue());
        $category->addMetaTitles(new LocalizedFallbackValue());
        $category->addMetaTitles(new LocalizedFallbackValue());
        $category->addMetaDescriptions(new LocalizedFallbackValue());
        $category->addMetaDescriptions(new LocalizedFallbackValue());
        $category->addMetaDescriptions(new LocalizedFallbackValue());
        $category->addMetaKeywords(new LocalizedFallbackValue());
        $category->addMetaKeywords(new LocalizedFallbackValue());
        $category->addMetaKeywords(new LocalizedFallbackValue());

        $event->expects($this->once())
            ->method('getData')
            ->willReturn($category);

        $this->manager->expects($this->exactly(9))
            ->method('persist');

        $categoryExtension->onPostSubmit($event);
    }

    public function testBuildFormContainsMetaElements()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->exactly(3))
            ->method('add')
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('addEventListener');

        $categoryExtension = new ProductFormExtension($this->registry);
        $categoryExtension->buildForm($builder, []);
    }
}
