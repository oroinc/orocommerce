<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Crawler;

trait SEOFrontendTrait
{
    /**
     * @param Crawler $crawler
     * @param array $metaTags
     */
    public function checkSEOFrontendMetaTags(Crawler $crawler, array $metaTags)
    {
        $metaCrawler = $crawler->filter('head > meta');
        foreach ($metaTags as $metaTag) {
            $tagCrawler = $metaCrawler->reduce(function (Crawler $node) use ($metaTag) {
                return $metaTag['name'] === $node->attr('name');
            });
            $actualContent = $tagCrawler->extract('content');
            $this->assertEquals($metaTag['content'], reset($actualContent));
        }
    }

    /**
     * @param string $entity
     * @param string $metaField
     * @return string
     */
    protected function getMetaContent($entity, $metaField)
    {
        $metadata = $this->getMetadataArray($entity);
        if (array_key_exists($entity, $metadata) && array_key_exists($metaField, $metadata[$entity])) {
            return $metadata[$entity][$metaField];
        }

        return '';
    }

    /**
     * @return string
     */
    protected function getMetaTitleName()
    {
        return 'title';
    }

    /**
     * @return string
     */
    protected function getMetaDescriptionName()
    {
        return 'description';
    }

    /**
     * @return string
     */
    protected function getMetaKeywordsName()
    {
        return 'keywords';
    }
}
