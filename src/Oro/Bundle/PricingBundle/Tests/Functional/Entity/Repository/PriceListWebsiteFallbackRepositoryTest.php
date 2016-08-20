<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

/**
 * @dbIsolation
 */
class PriceListWebsiteFallbackRepositoryTest extends AbstractFallbackRepositoryTest
{
    public function testGetWebsiteIdByDefaultFallback()
    {
        $expectedWebsitesReferences = ['CA', 'US', 'Default'];
        $actualWebsites = $this->doctrine->getRepository('OroPricingBundle:PriceListWebsiteFallback')
            ->getWebsiteIdByDefaultFallback();
        $this->assertCount(count($expectedWebsitesReferences), $actualWebsites);
        foreach ($actualWebsites as $actualWebsite) {
            $actualWebsite = $this->doctrine->getRepository('OroWebsiteBundle:Website')->find($actualWebsite['id']);
            $this->assertContains($actualWebsite->getName(), $expectedWebsitesReferences);
        }
    }
}
