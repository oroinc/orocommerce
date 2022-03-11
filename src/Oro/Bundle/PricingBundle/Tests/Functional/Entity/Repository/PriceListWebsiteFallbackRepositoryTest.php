<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class PriceListWebsiteFallbackRepositoryTest extends AbstractFallbackRepositoryTest
{
    public function testGetWebsiteIdByDefaultFallback()
    {
        $expectedWebsitesReferences = ['CA', 'US', 'Default'];
        $actualWebsites = $this->doctrine->getRepository(PriceListWebsiteFallback::class)
            ->getWebsiteIdByDefaultFallback();
        $this->assertCount(count($expectedWebsitesReferences), $actualWebsites);
        foreach ($actualWebsites as $actualWebsite) {
            $actualWebsite = $this->doctrine->getRepository(Website::class)->find($actualWebsite['id']);
            $this->assertContains($actualWebsite->getName(), $expectedWebsitesReferences);
        }
    }
}
