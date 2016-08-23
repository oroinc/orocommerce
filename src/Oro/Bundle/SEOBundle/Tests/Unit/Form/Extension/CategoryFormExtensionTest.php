<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\SEOBundle\Form\Extension\CategoryFormExtension;
use Oro\Bundle\SEOBundle\Tests\Unit\Entity\Stub\CategoryStub;

class CategoryFormExtensionTest extends \PHPUnit_Framework_TestCase
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

    public function testBuildFormContainsMetaElements()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->exactly(3))
            ->method('add')
            ->willReturn($builder);

        $categoryExtension = new CategoryFormExtension($this->registry);
        $categoryExtension->buildForm($builder, []);
    }
}
