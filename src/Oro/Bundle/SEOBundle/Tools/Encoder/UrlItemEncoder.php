<?php

namespace Oro\Bundle\SEOBundle\Tools\Encoder;

use Oro\Bundle\SEOBundle\Tools\Normalizer\UrlItemNormalizer;
use Oro\Component\SEO\Model\DTO\UrlItemInterface;

class UrlItemEncoder
{
    /**
     * @var UrlItemNormalizer
     */
    private $urlItemNormalizer;
    
    /**
     * @param UrlItemNormalizer $urlItemNormalizer
     */
    public function __construct(UrlItemNormalizer $urlItemNormalizer)
    {
        $this->urlItemNormalizer = $urlItemNormalizer;
    }

    /**
     * @param UrlItemInterface $urlItem
     * @return string
     */
    public function encode(UrlItemInterface $urlItem)
    {
        $domDocument = new \DOMDocument();
        $rootNodeElement = $domDocument->createElement(UrlItemInterface::ROOT_NODE_ELEMENT);
        $domDocument->appendChild($rootNodeElement);
        foreach ($this->urlItemNormalizer->normalize($urlItem) as $element => $value) {
            $rootNodeElement->appendChild($domDocument->createElement($element, $value));
        }
        
        return $domDocument->saveXML($rootNodeElement);
    }
}
