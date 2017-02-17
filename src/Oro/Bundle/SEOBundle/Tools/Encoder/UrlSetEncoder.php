<?php

namespace Oro\Bundle\SEOBundle\Tools\Encoder;

use Oro\Bundle\SEOBundle\Model\UrlSet;
use Oro\Bundle\SEOBundle\Tools\Normalizer\UrlItemNormalizer;
use Oro\Component\SEO\Model\DTO\UrlItemInterface;
use Oro\Component\SEO\Model\UrlSetInterface;

class UrlSetEncoder
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
     * @param UrlSetInterface $urlSet
     * @return string
     */
    public function encode(UrlSetInterface $urlSet)
    {
        $document = new \DOMDocument();
        $document->xmlVersion = '1.0';
        $document->encoding = 'UTF-8';

        $rootNodeElement = $document->createElement(UrlSetInterface::ROOT_NODE_ELEMENT);
        $rootNodeElement->setAttribute('xmlns', UrlSetInterface::ROOT_NODE_XMLNS);
        $document->appendChild($rootNodeElement);

        foreach ($urlSet->getUrlItems() as $urlItem) {
            $urlItemElement = $document->createElement(UrlItemInterface::ROOT_NODE_ELEMENT);
            $rootNodeElement->appendChild($urlItemElement);

            $this->addUrlItemElements($document, $urlItemElement, $urlItem);
        }
        
        return $document->saveXML();
    }

    /**
     * @param \DOMDocument $document
     * @param \DOMElement $urlItemElement
     * @param UrlItemInterface $urlItem
     */
    private function addUrlItemElements(\DOMDocument $document, \DOMElement $urlItemElement, UrlItemInterface $urlItem)
    {
        $normalizedUrlItem = $this->urlItemNormalizer->normalize($urlItem);
        foreach ($normalizedUrlItem as $elementName => $elementValue) {
            $element = $document->createElement($elementName, $elementValue);
            $urlItemElement->appendChild($element);
        }
    }
}
