<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

use OroB2B\Bundle\AccountBundle\EventListener\SystemConfigCategoryVisibilityListener;
use OroB2B\Bundle\AccountBundle\Visibility\Storage\CategoryVisibilityStorage;

class SystemConfigCategoryVisibilityListenerTest extends \PHPUnit_Framework_TestCase
{
    const CATEGORY_VISIBILITY_KEY = 'oro_b2b_account___category_visibility';
    const ANOTHER_KEY = 'oro_b2b_account___another_key';

    /**
     * @var CategoryVisibilityStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryVisibilityStorage;

    /**
     * @var SystemConfigCategoryVisibilityListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->categoryVisibilityStorage = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Visibility\Storage\CategoryVisibilityStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new SystemConfigCategoryVisibilityListener($this->categoryVisibilityStorage);
    }

    protected function tearDown()
    {
        unset($this->categoryVisibilityStorage, $this->listener);
    }

    /**
     * @dataProvider onSettingsSaveBeforeDataProvider
     * @param array $settings
     * @param bool $clearAll
     */
    public function testOnSettingsSaveBefore(array $settings, $clearAll)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigSettingsUpdateEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getSettings')
            ->will($this->returnValue($settings));

        $this->categoryVisibilityStorage->expects($clearAll ? $this->once() : $this->never())
            ->method('clearData');

        $this->listener->onSettingsSaveBefore($event);
    }

    /**
     * @return array
     */
    public function onSettingsSaveBeforeDataProvider()
    {
        return [
            'clear all' => [
                'settings' => [self::CATEGORY_VISIBILITY_KEY => ['value' => 'test']],
                'clearAll' => true
            ],
            'without clear all' => [
                'settings' => [self::ANOTHER_KEY => ['value' => 'test']],
                'clearAll' => false
            ],
            'empty settings' => [
                'settings' => [],
                'clearAll' => false
            ]
        ];
    }
}
