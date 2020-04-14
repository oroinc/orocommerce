<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Integration;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermSettingsType;
use Oro\Bundle\PaymentTermBundle\Integration\PaymentTermTransport;

class PaymentTermTransportTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentTermTransport */
    private $transport;

    protected function setUp(): void
    {
        $this->transport = new PaymentTermTransport();
    }

    public function testInitCompiles()
    {
        $this->transport->init(new PaymentTermSettings());
    }

    public function testGetSettingsFormTypeReturnsCorrectName()
    {
        static::assertSame(PaymentTermSettingsType::class, $this->transport->getSettingsFormType());
    }

    public function testGetSettingsEntityFQCNReturnsCorrectName()
    {
        static::assertSame(PaymentTermSettings::class, $this->transport->getSettingsEntityFQCN());
    }

    public function testGetLabelReturnsCorrectString()
    {
        static::assertSame('oro.paymentterm.settings.label', $this->transport->getLabel());
    }
}
