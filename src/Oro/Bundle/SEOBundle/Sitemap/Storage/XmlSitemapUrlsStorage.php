<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Storage;

use Oro\Component\SEO\Model\DTO\UrlItemInterface;

class XmlSitemapUrlsStorage extends AbstractXmlSitemapStorage
{
    /**
     * {@inheritdoc}
     */
    protected function startXmlTemplate(\XMLWriter $xmlWriter)
    {
        $xmlWriter->openMemory();

        $xmlWriter->startDocument('1.0', 'UTF-8');

        $xmlWriter->startElement('urlset');

        $xmlWriter->startAttribute('xmlns');
        $xmlWriter->text('http://www.sitemaps.org/schemas/sitemap/0.9');
        $xmlWriter->endAttribute();
    }

    /**
     * {@inheritdoc}
     */
    protected function fillItem(\XMLWriter $urlItemWriter, UrlItemInterface $urlItem)
    {
        $urlItemWriter->startElement('url');

        $this->appendElementIfNotEmpty($urlItemWriter, 'loc', $urlItem->getLocation());
        $this->appendElementIfNotEmpty($urlItemWriter, 'changefreq', $urlItem->getChangeFrequency());
        $this->appendElementIfNotEmpty($urlItemWriter, 'priority', $urlItem->getPriority());
        $this->appendElementIfNotEmpty($urlItemWriter, 'lastmod', $urlItem->getLastModification());

        $urlItemWriter->endElement();
    }
}
