<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Storage;

use Oro\Component\SEO\Model\DTO\UrlItemInterface;

class XmlSitemapIndexStorage extends AbstractXmlSitemapStorage
{
    /**
     * {@inheritdoc}
     */
    protected function startXmlTemplate(\XMLWriter $xmlWriter)
    {
        $xmlWriter->openMemory();

        $xmlWriter->startDocument('1.0', 'UTF-8');

        $xmlWriter->startElement('sitemapindex');

        $xmlWriter->startAttribute('xmlns');
        $xmlWriter->text('http://www.sitemaps.org/schemas/sitemap/0.9');
        $xmlWriter->endAttribute();
    }

    /**
     * {@inheritdoc}
     */
    protected function fillItem(\XMLWriter $urlItemWriter, UrlItemInterface $urlItem)
    {
        $urlItemWriter->startElement('sitemap');

        $this->appendElementIfNotEmpty($urlItemWriter, 'loc', $urlItem->getLocation());
        $this->appendElementIfNotEmpty($urlItemWriter, 'lastmod', $urlItem->getLastModification());

        $urlItemWriter->endElement();
    }
}
