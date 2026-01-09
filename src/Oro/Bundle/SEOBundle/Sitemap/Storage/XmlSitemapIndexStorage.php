<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Storage;

use Oro\Component\SEO\Model\DTO\UrlItemInterface;

/**
 * XML storage for sitemap index files.
 *
 * This class extends the abstract XML sitemap storage to provide storage for sitemap index files.
 * It generates XML output conforming to the sitemap index protocol specification, including references
 * to individual sitemap files and their last modification dates.
 */
class XmlSitemapIndexStorage extends AbstractXmlSitemapStorage
{
    #[\Override]
    protected function startXmlTemplate(\XMLWriter $xmlWriter)
    {
        $xmlWriter->openMemory();

        $xmlWriter->startDocument('1.0', 'UTF-8');

        $xmlWriter->startElement('sitemapindex');

        $xmlWriter->startAttribute('xmlns');
        $xmlWriter->text('http://www.sitemaps.org/schemas/sitemap/0.9');
        $xmlWriter->endAttribute();
    }

    #[\Override]
    protected function fillItem(\XMLWriter $urlItemWriter, UrlItemInterface $urlItem)
    {
        $urlItemWriter->startElement('sitemap');

        $this->appendElementIfNotEmpty($urlItemWriter, 'loc', $urlItem->getLocation());
        $this->appendElementIfNotEmpty($urlItemWriter, 'lastmod', $urlItem->getLastModification());

        $urlItemWriter->endElement();
    }
}
