<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Model\Action;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class VisibilitySettingsResolver extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            ]
        );
    }

    public function testCreate()
    {
        $productVisibility = new ProductVisibility();
        $productVisibility->setWebsite($this->getReference(LoadWebsiteData::WEBSITE1));
        $productVisibility->setVisibility(ProductVisibility::HIDDEN);

        $this->client->getContainer()->get('doctrine')->getManager()->persist($productVisibility);
        $this->client->getContainer()->get('doctrine')->getManager()->flush();

    }
}
