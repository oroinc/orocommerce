<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Entity;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ConsentTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testGetDefaultName()
    {
        $defaultName = 'default';
        $consent = new Consent();
        $this->addDefaultName($consent, $defaultName);

        $localizedName = new LocalizedFallbackValue();
        $localizedName->setString('localized')
            ->setLocalization(new Localization());

        $consent->addName($localizedName);

        $this->assertEquals($defaultName, $consent->getDefaultName());
    }

    public function testNoDefaultName()
    {
        $consent = new Consent();
        $this->assertNull($consent->getDefaultName());
    }

    /**
     * @param Consent $consent
     * @param string $name
     */
    protected function addDefaultName(Consent $consent, $name)
    {
        $defaultName = new LocalizedFallbackValue();
        $defaultName->setString($name);

        $consent->addName($defaultName);
    }
}
