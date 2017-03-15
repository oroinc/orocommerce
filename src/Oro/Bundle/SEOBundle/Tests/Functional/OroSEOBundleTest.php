<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OroSEOBundleTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testCompilerPassAddsFields()
    {
        $productListener = $this->getContainer()->get('oro_product.event_listener.product_content_variant_reindex');
        $categoryListener = $this->getContainer()->get('oro_catalog.event_listener.category_content_variant_index');

        $expectedValues = ['titles', 'metaDescriptions', 'metaKeywords'];
        $this->assertEquals($expectedValues, $productListener->getFields());
        $this->assertEquals($expectedValues, $categoryListener->getFields());
    }
}
