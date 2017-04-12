<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Provider;

use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentVariant;
use Oro\Bundle\ProductBundle\Provider\ContentVariantSegmentProvider;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadSegmentContentVariants;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ContentVariantSegmentProviderTest extends WebTestCase
{
    /**
     * @var ContentVariantSegmentProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadSegmentContentVariants::class]);

        $this->provider = new ContentVariantSegmentProvider(
            static::getContainer()->get('oro_entity.doctrine_helper'),
            TestContentVariant::class
        );
    }

    public function testGetContentVariantSegmentsNoClass()
    {
        $this->provider = new ContentVariantSegmentProvider(static::getContainer()->get('oro_entity.doctrine_helper'));

        $this->assertEmpty(iterator_to_array($this->provider->getContentVariantSegments()));
    }

    public function testGetContentVariantSegments()
    {
        $segments = [
            $this->getReference('segment_dynamic'),
            $this->getReference('segment_static')
        ];

        $this->assertEquals($segments, iterator_to_array($this->provider->getContentVariantSegments()));
    }

    public function testGetContentVariantsNoClass()
    {
        $this->provider = new ContentVariantSegmentProvider(static::getContainer()->get('oro_entity.doctrine_helper'));

        $this->assertEmpty(
            iterator_to_array($this->provider->getContentVariants($this->getReference('segment_dynamic')))
        );
        $this->assertEmpty(
            iterator_to_array($this->provider->getContentVariants($this->getReference('segment_static')))
        );
    }

    public function testGetContentVariants()
    {
        $this->assertEquals(
            [
                $this->getReference('test_segment_variant.1'),
                $this->getReference('test_segment_variant.2'),
            ],
            iterator_to_array($this->provider->getContentVariants($this->getReference('segment_dynamic')))
        );
        $this->assertEquals(
            [
                $this->getReference('test_segment_variant.3')
            ],
            iterator_to_array($this->provider->getContentVariants($this->getReference('segment_static')))
        );
    }

    public function testHasContentVariantNoClass()
    {
        $this->provider = new ContentVariantSegmentProvider(static::getContainer()->get('oro_entity.doctrine_helper'));

        $this->assertFalse($this->provider->hasContentVariant($this->getReference('segment_dynamic')));
        $this->assertFalse($this->provider->hasContentVariant($this->getReference('segment_static')));
        $this->assertFalse($this->provider->hasContentVariant($this->getReference('segment_dynamic_with_filter')));
    }

    public function testHasContentVariant()
    {
        $this->assertTrue($this->provider->hasContentVariant($this->getReference('segment_dynamic')));
        $this->assertTrue($this->provider->hasContentVariant($this->getReference('segment_static')));
        $this->assertFalse($this->provider->hasContentVariant($this->getReference('segment_dynamic_with_filter')));
    }
}
