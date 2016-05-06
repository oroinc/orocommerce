<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\Event\LifecycleEventArgs;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use OroB2B\Bundle\WebsiteBundle\EventListener\ORM\LocaleListener;
use OroB2B\Bundle\WebsiteBundle\Translation\Strategy\LocaleFallbackStrategy;

class LocaleListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LocaleListener
     */
    protected $listener;

    /**
     * @var LocaleFallbackStrategy|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $strategy;
    
    protected function setUp()
    {
        $this->strategy = $this->getMockBuilder(
            'OroB2B\Bundle\WebsiteBundle\Translation\Strategy\LocaleFallbackStrategy'
        )->disableOriginalConstructor()->getMock();
        $this->listener = new LocaleListener($this->strategy);
    }

    public function testPostPersist()
    {
        /** @var Locale $locale */
        $locale = $this->getMock('OroB2B\Bundle\WebsiteBundle\Entity\Locale');
        $args = $this->getLifecycleEventArgsMock();
        $this->strategy->expects($this->once())
            ->method('clearCache');
        $this->listener->postPersist($locale, $args);
    }

    public function testPostUpdate()
    {
        /** @var Locale $locale */
        $locale = $this->getMock('OroB2B\Bundle\WebsiteBundle\Entity\Locale');
        $args = $this->getLifecycleEventArgsMock();
        $this->strategy->expects($this->once())
            ->method('clearCache');
        $this->listener->postUpdate($locale, $args);
    }

    /**
     * @return LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLifecycleEventArgsMock()
    {
        return $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()->getMock();
    }
}
