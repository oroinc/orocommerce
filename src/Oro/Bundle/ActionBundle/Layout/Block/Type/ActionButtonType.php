<?php

namespace Oro\Bundle\ActionBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class ActionButtonType extends AbstractType
{
    const NAME = 'action_button';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['params', 'fromUrl', 'actionData', 'context']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $params = $options['params'];
        $frontendOptions = $params['frontendOptions'];
        $buttonOptions = $params['buttonOptions'];
        $actionUrl = $params['actionUrl'];
        $attributes = [];
        if (array_key_exists('id', $params)) {
            $attributes['id'] = $params['id'];
        }
        $attributes['href'] = $params['path'] ?: 'javascript:void(0);';
        $attributes['class'] = 'back icons-holder-text action-button';
        $titleRaw = array_key_exists('title', $frontendOptions) ? $frontendOptions['title'] : $params['label'];
        $title = $this->translator->trans($titleRaw);
        $attributes['title'] = $title;
        $attributes['data-from-url'] = $options['fromUrl'];
        $attributes = $this->setDialogParameters($frontendOptions, $actionUrl, $attributes, $title);
        $attributes['data-confirmation'] = array_key_exists('confirmation', $frontendOptions) ?
            $frontendOptions['confirmation'] : '';
        if (array_key_exists('page_component_module', $buttonOptions)) {
            $attributes['data-page-component-module'] = $buttonOptions['page_component_module'];
        }
        if (array_key_exists('page_component_options', $buttonOptions)) {
            $attributes['data-page-component-options'] = json_encode($buttonOptions['page_component_options']);
        }
        if (array_key_exists('data', $buttonOptions)) {
            foreach ($buttonOptions['data'] as $dataName => $dataValue) {
                $attributes['data-' . $dataName] = $dataValue;
            }
        }
        $view->vars['attr'] = $attributes;
        $view->vars['linkLabel'] = $title;
        $view->vars['buttonOptions'] = $buttonOptions;
    }

    /**
     * @param array $frontendOptions
     * @param string $actionUrl
     * @param array $attributes
     * @param string $title
     * @return array
     */
    protected function setDialogParameters($frontendOptions, $actionUrl, $attributes, $title)
    {
        if (array_key_exists('show_dialog', $frontendOptions) && !$frontendOptions['show_dialog']) {
            $attributes['data-page-url'] = $actionUrl;

            return $attributes;
        } else {
            $attributes['data-dialog-url'] = $actionUrl;
            $attributes['data-dialog-options'] = json_encode(
                [
                    'title' => $title,
                    'dialogOptions' => $frontendOptions['options']
                ]
            );

            return $attributes;
        }
    }
}
