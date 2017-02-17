<?php

namespace Oro\Bundle\SEOBundle\Model;

use Oro\Bundle\SEOBundle\Tools\Encoder\UrlItemEncoder;
use Oro\Bundle\SEOBundle\Tools\Normalizer\UrlItemNormalizer;
use Oro\Component\SEO\Model\DTO\UrlItemInterface;
use Oro\Component\SEO\Model\UrlSetInterface;

class UrlSet implements UrlSetInterface
{
    /**
     * @var UrlItemEncoder
     */
    private $urlItemEncoder;

    /**
     * @var UrlItemInterface[]
     */
    private $urlItems = [];

    /**
     * @var int
     */
    private $urlItemsCount = 0;

    /**
     * @var int
     */
    private $fileSize = 0;
    
    public function __construct()
    {
        $this->urlItemEncoder = new UrlItemEncoder(new UrlItemNormalizer());
        $this->fileSize += $this->getRootNodeAttributesLength();
    }

    /**
     * {@inheritdoc}
     */
    public function getUrlItems()
    {
        return $this->urlItems;
    }

    /**
     * {@inheritdoc}
     */
    public function addUrlItem(UrlItemInterface $urlItem)
    {
        $urlItemXmlSize = strlen($this->urlItemEncoder->encode($urlItem));
        if (!$this->isAvailableToAdd($urlItemXmlSize)) {
            return false;
        }

        $this->urlItems[] = $urlItem;
        $this->urlItemsCount++;
        $this->fileSize += $urlItemXmlSize;

        return true;
    }

    /**
     * @param int $urlItemXmlSize
     * @return bool
     */
    private function isAvailableToAdd($urlItemXmlSize)
    {
        return $this->urlItemsCount < static::URLS_LIMIT
            && $this->fileSize + $urlItemXmlSize < static::FILE_SIZE_LIMIT;
    }

    /**
     * @return int
     */
    private function getRootNodeAttributesLength()
    {
        return strlen(sprintf('xmlns="%s"', self::ROOT_NODE_XMLNS)) + 1;
    }
}
