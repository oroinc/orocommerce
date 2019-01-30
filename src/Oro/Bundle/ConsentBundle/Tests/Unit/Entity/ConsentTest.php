<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Entity;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Tests\Unit\Entity\Stub\Consent;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ConsentTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['owner', new User()],
            ['organization', new Organization()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
            ['mandatory', true, false],
            ['declinedNotification', true, false],
            ['contentNode', new ContentNode()]
        ];

        $this->assertPropertyAccessors(new Consent(), $properties);
    }

    public function testCollections()
    {
        $collections = [
            ['names', new LocalizedFallbackValue()],
            ['acceptances', new ConsentAcceptance()]
        ];

        $this->assertPropertyCollections(new Consent(), $collections);
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
