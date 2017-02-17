<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Tools\Normalizer;

use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Tools\Normalizer\UrlItemNormalizer;
use Oro\Component\SEO\Model\DTO\UrlItemInterface;

class UrlItemNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UrlItemNormalizer
     */
    private $normalizer;

    protected function setUp()
    {
        $this->normalizer = new UrlItemNormalizer();
    }

    /**
     * @dataProvider normalizeProvider
     * @param UrlItemInterface $urlItem
     * @param array $expectedData
     */
    public function testNormalize(UrlItemInterface $urlItem, array $expectedData)
    {
        $normalizedData = $this->normalizer->normalize($urlItem);
        $this->assertEquals($expectedData, $normalizedData);
    }

    /**
     * @return array
     */
    public function normalizeProvider()
    {
        $dateTime = new \DateTime();

        return [
            'normalize with full data' => [
                'urlItem' => new UrlItem('http://example.com/', 'daily', 0.5, $dateTime),
                'expectedData' => [
                    'loc' => 'http://example.com/',
                    'changefreq' => 'daily',
                    'priority' => 0.5,
                    'lastmod' => $dateTime->format(\DateTime::W3C),
                ],
            ],
            'normalize with short data' => [
                'urlItem' => new UrlItem('http://example.com/'),
                'expectedData' => [
                    'loc' => 'http://example.com/',
                ],
            ],
        ];
    }
}
