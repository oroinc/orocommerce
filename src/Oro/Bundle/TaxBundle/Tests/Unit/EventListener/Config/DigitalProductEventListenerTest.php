<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener\Config;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\TaxBundle\Entity\Repository\AbstractTaxCodeRepository;
use Oro\Bundle\TaxBundle\EventListener\Config\DigitalProductEventListener;

class DigitalProductEventListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var DigitalProductEventListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @var array
     */
    protected $data = [];

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new DigitalProductEventListener(
            $this->doctrineHelper,
            'Oro\Bundle\TaxBundle\Entity\ProductTaxCode',
            'digital_products_eu'
        );

        $this->data = ['CODE1', null, 1, new \stdClass(), '', 'CODE2', '2'];
    }

    public function testFormPreSetWithoutKey()
    {
        /** @var ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()->getMock();

        $event->expects($this->once())->method('getSettings')->willReturn([]);
        $this->doctrineHelper->expects($this->never())->method($this->anything());
        $event->expects($this->never())->method('setSettings');

        $this->listener->formPreSet($event);
    }

    public function testFormPreSet()
    {
        /** @var ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()->getMock();

        $event->expects($this->once())->method('getSettings')
            ->willReturn(['oro_tax___digital_products_eu' => ['value' => $this->data]]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|AbstractTaxCodeRepository $repository */
        $repository = $this->getMockBuilder('Oro\Bundle\TaxBundle\Entity\Repository\AbstractTaxCodeRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $taxCodes = [
            $this->getEntity('Oro\Bundle\TaxBundle\Entity\ProductTaxCode', ['code' => 'CODE1']),
            $this->getEntity('Oro\Bundle\TaxBundle\Entity\ProductTaxCode', ['code' => 'CODE2']),
            $this->getEntity('Oro\Bundle\TaxBundle\Entity\ProductTaxCode', ['code' => '2']),
        ];
        $repository->expects($this->once())->method('findByCodes')->with(['CODE1', 'CODE2', '2'])
            ->willReturn($taxCodes);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->willReturn($repository);

        $event->expects($this->once())->method('setSettings')->with(
            $this->callback(
                function ($settings) use ($taxCodes) {
                    $this->assertInternalType('array', $settings);
                    $this->assertArrayHasKey('oro_tax___digital_products_eu', $settings);
                    $this->assertInternalType('array', $settings['oro_tax___digital_products_eu']);
                    $this->assertArrayHasKey('value', $settings['oro_tax___digital_products_eu']);
                    $this->assertInternalType('array', $settings['oro_tax___digital_products_eu']['value']);

                    $this->assertEquals($taxCodes, $settings['oro_tax___digital_products_eu']['value']);

                    return true;
                }
            )
        );

        $this->listener->formPreSet($event);
    }

    public function testBeforeSaveWithoutKey()
    {
        /** @var ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()->getMock();

        $event->expects($this->once())->method('getSettings')->willReturn([]);
        $this->doctrineHelper->expects($this->never())->method($this->anything());
        $event->expects($this->never())->method('setSettings');

        $this->listener->beforeSave($event);
    }

    public function testBeforeSave()
    {
        /** @var ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()->getMock();

        $event->expects($this->once())->method('getSettings')
            ->willReturn(['oro_tax.digital_products_eu' => ['value' => $this->data]]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|AbstractTaxCodeRepository $repository */
        $repository = $this->getMockBuilder('Oro\Bundle\TaxBundle\Entity\Repository\AbstractTaxCodeRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $taxCodes = [
            $this->getEntity('Oro\Bundle\TaxBundle\Entity\ProductTaxCode', ['id' => 1, 'code' => 'CODE1']),
            $this->getEntity('Oro\Bundle\TaxBundle\Entity\ProductTaxCode', ['id' => 2, 'code' => 'CODE2']),
        ];

        $repository->expects($this->once())->method('findBy')->with(['id' => [1, 2]])->willReturn($taxCodes);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->willReturn($repository);

        $event->expects($this->once())->method('setSettings')->with(
            $this->callback(
                function ($settings) {
                    $this->assertInternalType('array', $settings);
                    $this->assertArrayHasKey('oro_tax.digital_products_eu', $settings);
                    $this->assertInternalType('array', $settings['oro_tax.digital_products_eu']);
                    $this->assertArrayHasKey('value', $settings['oro_tax.digital_products_eu']);
                    $this->assertInternalType('array', $settings['oro_tax.digital_products_eu']['value']);

                    $this->assertEquals(['CODE1', 'CODE2'], $settings['oro_tax.digital_products_eu']['value']);

                    return true;
                }
            )
        );

        $this->listener->beforeSave($event);
    }
}
