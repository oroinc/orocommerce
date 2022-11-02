<?php

namespace Oro\Bundle\ConsentBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\CMSBundle\Migrations\Data\AbstractLoadPageData;

/**
 * Adds consent cms pages
 */
class LoadConsentCmsPagesDemoData extends AbstractLoadPageData
{
    /**
     * {@inheritdoc}
     */
    public function getFilePaths()
    {
        return $this->getFilePathsFromLocator(__DIR__ . '/data/consent_cms_pages.yml');
    }
}
