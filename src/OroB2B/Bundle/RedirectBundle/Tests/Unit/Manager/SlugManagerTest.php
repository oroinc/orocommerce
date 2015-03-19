<?php

namespace OroB2B\Bundle\RedirectBundle\Tests\Unit\Manager;

use OroB2B\Bundle\RedirectBundle\Entity\Slug;
use OroB2B\Bundle\RedirectBundle\Manager\SlugManager;

class SlugManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     *  @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $doctrine;

    public function setUp()
    {
        $this->doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(array('getManager'))
            ->getMock();
    }

    /**
     * @dataProvider incrementUrlDataProvider
     * @param  string $url
     * @param  string $expectedUrl
     */
    public function testIncrementUrl($url, $expectedUrl)
    {
        $manager = new SlugManager($this->doctrine);
        $this->assertEquals($expectedUrl, $manager->incrementUrl($url));
    }

    /**
     * Data provider function for testIncrementUrl
     * @return  array()
     */
    public function incrementUrlDataProvider()
    {
        return array(
            array('domain.com/hvac-equipment/detection-kits', 'domain.com/hvac-equipment/detection-kits-1'),
            array('domain.com/hvac-equipment/detection-kits-1', 'domain.com/hvac-equipment/detection-kits-2'),
            array('domain.com/hvac-equipment/detection-kits1', 'domain.com/hvac-equipment/detection-kits1-1'),
            array('domain.com/hvac-equipment/detection-kits-01', 'domain.com/hvac-equipment/detection-kits-2'),
            array('domain.com/hvac-equipment/detection-kits-001', 'domain.com/hvac-equipment/detection-kits-2'),
        );
    }

    public function testSetUniqueUrlForSlug()
    {
        $slug = $this->getMockBuilder('OroB2B\Bundle\RedirectBundle\Entity\Slug')
            ->disableOriginalConstructor()
            ->setMethods(array('getId', 'getUrl', 'setUrl'))
            ->getMock();
        $slug
            ->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));
        $slug
            ->expects($this->exactly(2))
            ->method('getUrl')
            ->will($this->returnValue('domain.com/hvac-equipment/detection-kits'));
        $slug
            ->expects($this->once())
            ->method('setUrl')
            ->with($this->equalTo('domain.com/hvac-equipment/detection-kits-2'));

        $existedSlug = $this->getMockBuilder('OroB2B\Bundle\RedirectBundle\Entity\Slug')
            ->disableOriginalConstructor()
            ->setMethods(array('getId', 'getUrl', 'setUrl'))
            ->getMock();
        $existedSlug
            ->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(2));

        $manager = $this->getMockBuilder('OroB2B\Bundle\RedirectBundle\Manager\SlugManager')
            ->disableOriginalConstructor()
            ->setMethods(array('findSlugByUrl'))
            ->getMock();
        $manager
            ->expects($this->at(0))
            ->method('findSlugByUrl')
            ->will($this->returnValue($existedSlug));
        $manager
            ->expects($this->at(1))
            ->method('findSlugByUrl')
            ->will($this->returnValue(new Slug()));
        $manager
            ->expects($this->at(2))
            ->method('findSlugByUrl')
            ->will($this->returnValue(null));

        $manager->setUniqueUrlForSlug($slug);
    }
}
