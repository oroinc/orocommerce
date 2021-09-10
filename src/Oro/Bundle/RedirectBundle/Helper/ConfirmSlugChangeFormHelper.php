<?php

namespace Oro\Bundle\RedirectBundle\Helper;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Form\Type\SlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\RedirectBundle\Model\TextSlugPrototypeWithRedirect;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class ConfirmSlugChangeFormHelper
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function addConfirmSlugChangeOptionsLocalized(FormView $view, FormInterface $form, array $options)
    {
        $valuesField = sprintf(
            '[name^="%s[%s][values]"]',
            $view->vars['full_name'],
            LocalizedSlugWithRedirectType::SLUG_PROTOTYPES_FIELD_NAME
        );

        $this->addConfirmSlugChangeComponentOptions(
            $view,
            $valuesField,
            LocalizedSlugWithRedirectType::CREATE_REDIRECT_FIELD_NAME
        );

        if ($this->isRedirectConfirmationDisabled($options) || $this->isEmptyCollection($form->getData())) {
            $view->vars['confirm_slug_change_component_options']['disabled'] = true;
        }
    }

    public function addConfirmSlugChangeOptions(FormView $view, FormInterface $form, array $options)
    {
        $valuesField = sprintf(
            '[name^="%s[%s]"]',
            $view->vars['full_name'],
            SlugWithRedirectType::TEXT_SLUG_PROTOTYPE_FIELD_NAME
        );

        $this->addConfirmSlugChangeComponentOptions(
            $view,
            $valuesField,
            SlugWithRedirectType::CREATE_REDIRECT_FIELD_NAME
        );

        if ($this->isRedirectConfirmationDisabled($options) || $this->isEmptyTextSlugField($form->getData())) {
            $view->vars['confirm_slug_change_component_options']['disabled'] = true;
        }
    }

    /**
     * @param FormView $view
     * @param string $valuesField
     * @param string $createRedirectField
     */
    private function addConfirmSlugChangeComponentOptions(FormView $view, $valuesField, $createRedirectField)
    {
        $view->vars['confirm_slug_change_component_options'] = [
            'slugFields' => $valuesField,
            'createRedirectCheckbox' => sprintf('[name^="%s[%s]"]', $view->vars['full_name'], $createRedirectField),
            'disabled' => false,
        ];
    }

    /**
     * @param SlugPrototypesWithRedirect $data
     * @return bool
     */
    private function isEmptyCollection(SlugPrototypesWithRedirect $data)
    {
        return $data->getSlugPrototypes()->isEmpty();
    }

    /**
     * @param TextSlugPrototypeWithRedirect $data
     * @return bool
     */
    private function isEmptyTextSlugField(TextSlugPrototypeWithRedirect $data)
    {
        return empty($data->getTextSlugPrototype());
    }

    /**
     * @param array|\ArrayAccess $options
     * @return bool
     */
    private function isRedirectConfirmationDisabled($options)
    {
        return !$options['create_redirect_enabled']
            || $this->configManager->get('oro_redirect.redirect_generation_strategy') !== Configuration::STRATEGY_ASK;
    }
}
