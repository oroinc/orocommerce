<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Tools\Encoder;

use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Model\UrlSet;
use Oro\Bundle\SEOBundle\Tools\Encoder\UrlSetEncoder;
use Oro\Bundle\SEOBundle\Tools\Normalizer\UrlItemNormalizer;
use Oro\Component\SEO\Model\UrlSetInterface;

class UrlSetEncoderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UrlSetEncoder
     */
    private $encoder;
    
    protected function setUp()
    {
        $this->encoder = new UrlSetEncoder(new UrlItemNormalizer());
    }

    /**
     * @dataProvider encodeProvider
     * @param UrlSetInterface $urlSet
     * @param string $expectedData
     */
    public function testEncode(UrlSetInterface $urlSet, $expectedData)
    {
        $encodedData = $this->encoder->encode($urlSet);
        $this->assertEquals($expectedData, $encodedData);
    }

    /**
     * @return array
     */
    public function encodeProvider()
    {
        $location = 'http://example.com/';
        $urlItem = new UrlItem($location);
        $urlSet = new UrlSet();
        $urlSet->addUrlItem($urlItem);

        return [
            'encode with url item' => [
                'urlSet' => $urlSet,
                'expectedData' => sprintf(  
                    "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"%s\"><url><loc>%s</loc></url></urlset>\n",
                    UrlSetInterface::ROOT_NODE_XMLNS,
                    $location
                ),
            ],
            'encode without url item' => [
                'urlItem' => new UrlSet(),
                'expectedData' => sprintf(
                    "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"%s\"/>\n",
                    UrlSetInterface::ROOT_NODE_XMLNS
                ),
            ],
        ];
    }
}
