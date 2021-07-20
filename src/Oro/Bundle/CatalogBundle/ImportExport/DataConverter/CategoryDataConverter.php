<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\DataConverter;

use Oro\Bundle\LocaleBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter;

/**
 * Data converter for Category.
 * Additionally is responsible for:
 * - removes "organization.name" column from export file
 */
class CategoryDataConverter extends LocalizedFallbackValueAwareDataConverter
{
    /**
     * {@inheritdoc}
     */
    protected function receiveHeaderConversionRules()
    {
        $conversionRules = parent::receiveHeaderConversionRules();

        // Adds parentCategory title conversion rule.
        $conversionRules[sprintf('parentCategory%stitle', $this->relationDelimiter)]
            = sprintf('parentCategory%1$stitles%1$sdefault%1$sstring', $this->convertDelimiter);

        // Removes organization conversion rule if not allowed.
        if (!$this->isOrganizationColumnAllowed()) {
            unset($conversionRules[sprintf('organization%sname', $this->relationDelimiter)]);
        }

        return $conversionRules;
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        $header = parent::getBackendHeader();

        // Adds parentCategory title header.
        $header = array_values($header);
        $parentCategoryIdOffset = array_search(sprintf('parentCategory%sid', $this->convertDelimiter), $header, false);
        $parentCategoryTitle = sprintf('parentCategory%1$stitles%1$sdefault%1$sstring', $this->convertDelimiter);
        array_splice($header, $parentCategoryIdOffset + 1, 0, $parentCategoryTitle);

        // Removes organization header if not allowed.
        if (!$this->isOrganizationColumnAllowed()) {
            $key = array_search(sprintf('organization%sname', $this->convertDelimiter), $header, false);
            unset($header[$key]);
        }

        return $header;
    }

    protected function isOrganizationColumnAllowed(): bool
    {
        return false;
    }
}
