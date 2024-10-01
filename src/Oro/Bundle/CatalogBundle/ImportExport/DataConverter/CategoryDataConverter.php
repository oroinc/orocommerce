<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\DataConverter;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\LocaleBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Data converter for Category.
 * Additionally is responsible for:
 * - removes "organization.name" column from export file
 */
class CategoryDataConverter extends LocalizedFallbackValueAwareDataConverter
{
    #[\Override]
    protected function receiveHeaderConversionRules(): array
    {
        $conversionRules = parent::receiveHeaderConversionRules();

        $categoryHeaders = $this->getCategoryFieldHeaders();
        // Adds parentCategory title conversion rule.
        $conversionRules[sprintf(
            '%s%s%s',
            $categoryHeaders['parentCategory'],
            $this->relationDelimiter,
            $categoryHeaders['titles']
        )] = sprintf('parentCategory%1$stitles%1$sdefault%1$sstring', $this->convertDelimiter);

        // Removes organization conversion rule if not allowed.
        if (!$this->isOrganizationColumnAllowed()) {
            $orgHeaders = $this->getOrganizationFieldHeaders();
            $key = sprintf('%s%s%s', $orgHeaders['organization'], $this->relationDelimiter, $orgHeaders['name']);
            unset($conversionRules[$key]);
        }

        return $conversionRules;
    }

    #[\Override]
    protected function getBackendHeader(): array
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

    private function getCategoryFieldHeaders(): array
    {
        return [
            'parentCategory' =>
                $this->fieldHelper->getConfigValue(Category::class, 'parentCategory', 'header', 'parentCategory'),
            'titles' =>
                $this->fieldHelper->getConfigValue(Category::class, 'titles', 'header', 'titles')
        ];
    }

    private function getOrganizationFieldHeaders(): array
    {
        return [
            'organization' =>
                $this->fieldHelper->getConfigValue(Category::class, 'organization', 'header', 'organization'),
            'name' =>
                $this->fieldHelper->getConfigValue(Organization::class, 'name', 'header', 'name')
        ];
    }
}
