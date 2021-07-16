<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Storage;

use Oro\Component\SEO\Model\DTO\UrlItemInterface;

abstract class AbstractXmlSitemapStorage implements SitemapStorageInterface
{
    const URL_NUMBER_LIMIT = 10000;
    const FILE_SIZE_LIMIT = 10485760; // 10 MB

    /**
     * @var int
     */
    private $urlsNumberLimit;

    /**
     * @var int
     */
    private $fileSizeLimit;

    /**
     * @var int
     */
    private $fileSize;

    /**
     * @var int
     */
    private $urlItemsCount = 0;

    /**
     * @var \XMLWriter
     */
    private $xmlWriter;

    /**
     * @var bool
     */
    private $locked = false;

    /**
     * @param int $urlsNumberLimit
     * @param int $fileSizeLimit
     */
    public function __construct($urlsNumberLimit = self::URL_NUMBER_LIMIT, $fileSizeLimit = self::FILE_SIZE_LIMIT)
    {
        $this->urlsNumberLimit = $urlsNumberLimit;
        $this->fileSizeLimit = $fileSizeLimit;
        $this->xmlWriter = new \XMLWriter();

        $this->startXmlTemplate($this->xmlWriter);
        $this->calculateTemplateSize();
    }

    /**
     * {@inheritdoc}
     */
    public function addUrlItem(UrlItemInterface $urlItem)
    {
        if ($this->urlItemsCount === $this->urlsNumberLimit || !$this->appendItem($urlItem)) {
            return false;
        }

        $this->urlItemsCount++;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        $this->finishXmlTemplate($this->xmlWriter);
        $this->locked = true;

        return $this->xmlWriter->outputMemory(false);
    }

    /**
     * {@inheritdoc}
     */
    public function getUrlItemsCount()
    {
        return $this->urlItemsCount;
    }

    public function __destruct()
    {
        $this->xmlWriter->flush();
    }

    /**
     * Produces following xml part:
     * <?xml version="1.0" encoding="UTF-8"?>
     * <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
     */
    abstract protected function startXmlTemplate(\XMLWriter $xmlWriter);

    abstract protected function fillItem(\XMLWriter $urlItemWriter, UrlItemInterface $urlItem);

    /**
     * Produces following xml part:
     * <elementName>$elementValue</elementName>
     *
     * @param \XMLWriter $writer
     * @param string $elementName
     * @param string|int $elementValue
     */
    protected function appendElementIfNotEmpty(\XMLWriter $writer, $elementName, $elementValue)
    {
        if ($elementValue) {
            $writer->startElement($elementName);
            $writer->text($elementValue);
            $writer->endElement();
        }
    }

    /**
     * Appends url item xml:
     * <url>
     *   <loc>http://somelocation</loc>
     *   <changefreq>daily</changefreq>
     *   <priority>1</priority>
     *   <lastmod>2017-05-03T15:45:00+03:00</lastmod>
     * </url>
     *
     * @param UrlItemInterface $urlItem
     * @return bool
     */
    private function appendItem(UrlItemInterface $urlItem)
    {
        if ($this->locked) {
            return false;
        }

        $urlItemWriter = new \XMLWriter();
        $urlItemWriter->openMemory();
        $this->fillItem($urlItemWriter, $urlItem);
        $urlItemXml = $urlItemWriter->outputMemory();

        $urlItemXmlSize = strlen($urlItemXml);
        if ($urlItemXmlSize + $this->fileSize > $this->fileSizeLimit) {
            return false;
        }

        $this->fileSize += $urlItemXmlSize;
        $this->xmlWriter->writeRaw($urlItemXml);

        return true;
    }

    /**
     * Produces following xml part:
     * </urlset>
     */
    private function finishXmlTemplate(\XMLWriter $xmlWriter)
    {
        if (!$this->locked) {
            $xmlWriter->endElement();
        }
    }

    private function calculateTemplateSize()
    {
        $xmlWriter = new \XMLWriter();

        $this->startXmlTemplate($xmlWriter);

        $dummyElement = '';
        $xmlWriter->writeRaw($dummyElement);

        $this->finishXmlTemplate($xmlWriter);

        $this->fileSize = strlen($xmlWriter->outputMemory());
    }
}
