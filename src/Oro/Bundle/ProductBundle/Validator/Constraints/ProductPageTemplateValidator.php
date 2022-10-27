<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Component\Layout\Extension\Theme\Manager\PageTemplatesManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates selected product page template.
 */
class ProductPageTemplateValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_page_template_validator';

    /** @var PageTemplatesManager */
    private $pageTemplatesManager;

    public function __construct(PageTemplatesManager $pageTemplatesManager)
    {
        $this->pageTemplatesManager = $pageTemplatesManager;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($entityFieldFallbackValue, Constraint $constraint)
    {
        if (is_null($entityFieldFallbackValue)) {
            return;
        }

        $selectedValue = $this->getSelectedValue($entityFieldFallbackValue, $constraint->route);

        if (!$this->selectedValuesAreValid($selectedValue, $this->getValidValues($constraint->route))) {
            $this->context->addViolation($constraint->message);
        }
    }

    /**
     * Check if the selected value in the form was in the allowed choices list
     *
     * @param $selectedValues
     * @param $choices
     * @return bool
     */
    private function selectedValuesAreValid($selectedValues, $choices)
    {
        if (!is_array($selectedValues)) {
            $selectedValues = [$selectedValues];
        }

        foreach ($selectedValues as $selectedValue) {
            if (!in_array($selectedValue, $choices)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retrieve the valid values for this field and route
     *
     * @param $route
     * @return array
     */
    private function getValidValues($route)
    {
        $pageTemplates = $this->pageTemplatesManager->getRoutePageTemplates();

        $validValues = $pageTemplates[$route] ?? [];

        /* values are saved in "choices" array as values in the form. ex:
         choices = [
          "short" => "Short label",
          "two-columns" => "Two-columns label",
          "list" => "List label",
        ]
        */
        $validValues = array_keys($validValues['choices']);

        return array_merge($validValues, [null]);
    }

    /**
     * @param $entityFieldFallbackValue
     * @param $route
     * @return string|null
     */
    private function getSelectedValue($entityFieldFallbackValue, $route)
    {
        return $entityFieldFallbackValue->getArrayValue()[$route] ?? null;
    }
}
