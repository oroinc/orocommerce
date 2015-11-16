<?php

namespace OroB2B\Bundle\FallbackBundle\Tests\Functional\ImportExport\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\FallbackBundle\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizer;

/**
 * @covers \OroB2B\Bundle\FallbackBundle\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizer
 * @dbIsolation
 */
class LocalizedFallbackValueCollectionNormalizerTest extends WebTestCase
{
    use EntityTrait;

    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->initClient();


        $this->loadFixtures(
            ['OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData']
        );
    }

    /**
     * @param array $actualData
     * @param array $expectedData
     *
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(array $actualData, array $expectedData = [])
    {
        /** @var LocalizedFallbackValueCollectionNormalizer $normalizer */
        $normalizer = new LocalizedFallbackValueCollectionNormalizer(
            $this->getContainer()->get('doctrine'),
            $this->getContainer()->getParameter('orob2b_fallback.entity.localized_fallback_value.class'),
            $this->getContainer()->getParameter('orob2b_website.locale.class')
        );

        $class = $this->getContainer()->getParameter('orob2b_fallback.entity.localized_fallback_value.class');

        $actualData = array_map(
            function ($data) use ($class) {
                return $this->getEntity($class, $data);
            },
            $actualData
        );

        $normalizer->normalize(new ArrayCollection($actualData), $expectedData);
    }

    /**
     * @return array
     */
    public function normalizeDataProvider()
    {
        return [
            [[['fallback' => 'system', 'string' => 'value', 'locale' => null]]],
        ];
    }
}
