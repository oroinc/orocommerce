<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConsentBundle\EventListener\SystemConfigListener;
use Oro\Bundle\ContactUsBundle\Entity\ContactReason;
use Oro\Bundle\ContactUsBundle\Tests\Unit\Stub\ContactReasonStub;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;

class SystemConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var SystemConfigListener */
    private $systemConfigListener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->systemConfigListener = new SystemConfigListener($this->doctrineHelper, $this->configManager);
    }

    /**
     * @dataProvider invalidSettingsDataProvider
     *
     * @param array $settings
     */
    public function testOnFormPreSetDataInvalidSettings($settings)
    {
        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);

        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->systemConfigListener->onFormPreSetData($event);
    }

    /**
     * @dataProvider invalidSettingsDataProvider
     *
     * @param array $settings
     */
    public function testOnSettingsSaveBeforeInvalidSettings($settings)
    {
        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);

        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->systemConfigListener->onSettingsSaveBefore($event);
    }

    public function testOnFormPreSetData()
    {
        $id = 42;
        $key = 'oro_consent___consent_contact_reason';

        $contactReason = $this->getEntity(ContactReasonStub::class, ['id' => $id]);

        $settings = [$key => ['value' => $id]];

        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('find')
            ->with(ContactReason::class, $id)
            ->will($this->returnValue($contactReason));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with(ContactReason::class)
            ->will($this->returnValue($manager));

        $this->systemConfigListener->onFormPreSetData($event);

        $this->assertEquals([$key => ['value' => $contactReason]], $event->getSettings());
    }

    public function testOnSettingsSaveBefore()
    {
        $id = 42;
        $contactReason = $this->getEntity(ContactReasonStub::class, ['id' => $id]);

        $settings = ['value' => $contactReason];

        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);

        $this->systemConfigListener->onSettingsSaveBefore($event);

        $this->assertEquals(['value' => $id], $event->getSettings());
    }

    public function testOnPreRemoveInvalid()
    {
        $id = 42;
        $key = 'oro_consent.consent_contact_reason';
        $contactReason = $this->getEntity(ContactReasonStub::class, ['id' => 44]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($id);

        $this->configManager->expects($this->never())
            ->method('reset');

        $this->configManager->expects($this->never())
            ->method('flush');

        $this->systemConfigListener->onPreRemove($contactReason);
    }

    public function testOnPreRemove()
    {
        $id = 42;
        $key = 'oro_consent.consent_contact_reason';
        $contactReason = $this->getEntity(ContactReasonStub::class, ['id' => 42]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($id);

        $this->configManager->expects($this->once())
            ->method('reset')
            ->with($key);

        $this->configManager->expects($this->once())
            ->method('flush');

        $this->systemConfigListener->onPreRemove($contactReason);
    }

    /**
     * @return array
     */
    public function invalidSettingsDataProvider()
    {
        return [
            [[null]],
            [[]],
            [['x' => 'y']],
            [[new \stdClass()]]
        ];
    }
}
