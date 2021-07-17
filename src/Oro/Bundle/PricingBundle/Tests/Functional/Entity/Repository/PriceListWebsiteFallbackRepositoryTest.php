<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListWebsiteFallbackRepository;
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

    /**
     * @dataProvider fallbackDataProvider
     * @param string $websiteReference
     * @param bool $expected
     */
    public function testHasFallbackOnNextLevel($websiteReference, $expected)
    {
        /** @var Website $website */
        $website = $this->getReference($websiteReference);

        /** @var PriceListWebsiteFallbackRepository $repo */
        $repo = $this->doctrine->getRepository(PriceListWebsiteFallback::class);
        $this->assertEquals($expected, $repo->hasFallbackOnNextLevel($website));
    }

    public function fallbackDataProvider(): array
    {
        return [
            'defined fallback to previous level' => ['US', true],
            'default fallback to previous level' => ['CA', true],
            'default fallback to current level' => ['Canada', false]
        ];
    }
}
