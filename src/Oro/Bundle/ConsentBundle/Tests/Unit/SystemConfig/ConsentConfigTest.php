<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\SystemConfig;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;

class ConsentConfigTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConsentConfig */
    private $consentConfig;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->consentConfig = new ConsentConfig();
    }

    public function testConsent()
    {
        $this->assertNull($this->consentConfig->getConsent());

        $consent = new Consent();
        $this->consentConfig->setConsent($consent);
        $this->assertSame($consent, $this->consentConfig->getConsent());
    }

    public function testSortOrder()
    {
        $this->assertNull($this->consentConfig->getSortOrder());

        $this->consentConfig->setSortOrder(42);
        $this->assertEquals(42, $this->consentConfig->getSortOrder());
    }
}
