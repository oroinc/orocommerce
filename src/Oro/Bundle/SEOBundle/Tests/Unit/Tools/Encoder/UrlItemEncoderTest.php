<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Tools\Encoder;

use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Tools\Encoder\UrlItemEncoder;
use Oro\Bundle\SEOBundle\Tools\Normalizer\UrlItemNormalizer;
use Oro\Component\SEO\Model\DTO\UrlItemInterface;

class UrlItemEncoderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UrlItemEncoder
     */
    private $encoder;
    
    protected function setUp()
    {
        $this->encoder = new UrlItemEncoder(new UrlItemNormalizer());
    }

    /**
     * @dataProvider encodeProvider
     * @param UrlItemInterface $urlItem
     * @param string $expectedData
     */
    public function testEncode(UrlItemInterface $urlItem, $expectedData)
    {
        $encodedData = $this->encoder->encode($urlItem);
        $this->assertEquals($expectedData, $encodedData);
    }

    /**
     * @return array
     */
    public function encodeProvider()
    {
        $location = 'http://example.com/';
        $changeFrequency = 'daily';
        $priority = 0.5;
        $lastModification = new \DateTime();

        return [
            'encode with full data' => [
                'urlItem' => new UrlItem($location, $changeFrequency, $priority, $lastModification),
                'expectedData' => sprintf(  
                    '<url><loc>%s</loc><changefreq>%s</changefreq><priority>%s</priority><lastmod>%s</lastmod></url>',
                    $location,
                    $changeFrequency,
                    $priority,
                    $lastModification->format(\DateTime::W3C)
                ),
            ],
            'encode with short data' => [
                'urlItem' => new UrlItem($location),
                'expectedData' => sprintf(
                    '<url><loc>%s</loc></url>',
                    $location
                ),
            ],
        ];
    }
}
