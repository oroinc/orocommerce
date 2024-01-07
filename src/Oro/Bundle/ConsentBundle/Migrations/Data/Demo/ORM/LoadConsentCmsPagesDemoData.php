<?php

namespace Oro\Bundle\ConsentBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\CMSBundle\Migrations\Data\AbstractLoadPageData;

/**
 * Loads consent CMS pages.
 */
class LoadConsentCmsPagesDemoData extends AbstractLoadPageData
{
    /**
     * {@inheritDoc}
     */
    protected function getFilePaths(): string
    {
        return $this->getFilePathsFromLocator(__DIR__ . '/data/consent_cms_pages.yml');
    }
}
