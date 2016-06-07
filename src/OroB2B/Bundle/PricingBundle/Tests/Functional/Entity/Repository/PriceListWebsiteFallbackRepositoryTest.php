<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

/**
 * @dbIsolation
 */
class PriceListWebsiteFallbackRepositoryTest extends AbstractFallbackRepositoryTest
{
    public function testGetWebsiteIdByDefaultFallback()
    {
        $expectedWebsitesReferences = ['CA', 'US', 'Default'];
        $actualWebsites = $this->doctrine->getRepository('OroB2BPricingBundle:PriceListWebsiteFallback')
            ->getWebsiteIdByDefaultFallback();
        $this->assertCount(count($expectedWebsitesReferences), $actualWebsites);
        foreach ($actualWebsites as $actualWebsite) {
            $actualWebsite = $this->doctrine->getRepository('OroB2BWebsiteBundle:Website')->find($actualWebsite['id']);
            $this->assertContains($actualWebsite->getName(), $expectedWebsitesReferences);
        }
    }
}
