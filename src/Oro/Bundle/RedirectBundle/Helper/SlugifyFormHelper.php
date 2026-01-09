<?php

namespace Oro\Bundle\RedirectBundle\Helper;

use Symfony\Component\Form\FormView;

/**
 * Provides helper methods for configuring slug suggestion UI components.
 *
 * This helper prepares form views with the necessary configuration options for displaying
 * slug suggestion functionality. It handles both localized and non-localized slug forms,
 * enabling automatic slug generation suggestions based on source field values.
 */
class SlugifyFormHelper
{
    public function addSlugifyOptionsLocalized(FormView $view, array $options)
    {
        $this->addSlugifyComponentOptions($view, $options, '[name^="%s[values]"]');
    }

    public function addSlugifyOptions(FormView $view, array $options)
    {
        $this->addSlugifyComponentOptions($view, $options, '[name^="%s"]');
    }

    /**
     * @param FormView $view
     * @param array $options
     * @param string $fieldPattern
     */
    private function addSlugifyComponentOptions(FormView $view, array $options, $fieldPattern)
    {
        if (
            empty($options['slug_suggestion_enabled'])
            || empty($options['source_field'])
            || empty($view->parent)
        ) {
            return;
        }

        $parent = $this->getHighestParent($view);
        $sourceFieldName = sprintf('%s[%s]', $parent->vars['full_name'], $options['source_field']);
        $targetFieldName = $view->vars['full_name'];

        $view->vars['slugify_component_options'] = [
            'source' => sprintf($fieldPattern, $sourceFieldName),
            'target' => sprintf($fieldPattern, $targetFieldName),
            'slugify_route' => $options['slugify_route'],
        ];
    }

    /**
     * @param FormView $view
     * @return FormView
     */
    private function getHighestParent(FormView $view)
    {
        while ($view->parent) {
            $view = $view->parent;
        }

        return $view;
    }
}
