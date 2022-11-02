<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Integration;

use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Form\Type\MoneyOrderSettingsType;
use Oro\Bundle\MoneyOrderBundle\Integration\MoneyOrderTransport;

class MoneyOrderTransportTest extends \PHPUnit\Framework\TestCase
{
    /** @var MoneyOrderTransport */
    private $transport;

    protected function setUp(): void
    {
        $this->transport = new MoneyOrderTransport();
    }

    public function testInitCompiles()
    {
        $this->transport->init(new MoneyOrderSettings());
    }

    public function testGetSettingsFormTypeReturnsCorrectName()
    {
        static::assertSame(MoneyOrderSettingsType::class, $this->transport->getSettingsFormType());
    }

    public function testGetSettingsEntityFQCNReturnsCorrectName()
    {
        static::assertSame(MoneyOrderSettings::class, $this->transport->getSettingsEntityFQCN());
    }

    public function testGetLabelReturnsCorrectString()
    {
        static::assertSame('oro.money_order.settings.label', $this->transport->getLabel());
    }
}
