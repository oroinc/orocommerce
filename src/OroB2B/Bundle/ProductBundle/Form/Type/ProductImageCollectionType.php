<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class ProductImageCollectionType extends AbstractType
{
    const NAME = 'orob2b_product_image_collection';

    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * @param ThemeManager $themeManager
     * @param RequestStack $requestStack
     */
    public function __construct(ThemeManager $themeManager, RequestStack $requestStack)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $imageTypeConfigs = $this->collectThemesImageTypeConfigs($this->themeManager);

        $resolver->setDefaults([
            'type' => ProductImageType::NAME,
            'options' => [
                'image_type_configs' => $imageTypeConfigs
            ],
            'image_type_configs' => $imageTypeConfigs,
        ]);

        $resolver->setAllowedTypes('image_type_configs', 'array');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['imageTypeConfigs'] = $options['image_type_configs'];
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param ThemeManager $themeManager
     * @return \string[]
     */
    protected function collectThemesImageTypeConfigs(ThemeManager $themeManager)
    {
        $configs = [];
        $themes = $themeManager->getAllThemes();
        foreach ($themes as $theme) {
            $configs += $this->collectThemeImageTypeConfigs($theme);
        }
        return $configs;
    }

    /**
     * @param Theme $theme
     * @return array
     */
    protected function collectThemeImageTypeConfigs(Theme $theme)
    {
        $imageTypeConfigs = $theme->getDataByKey('images', ['types' => []])['types'];

        return $imageTypeConfigs;
    }
}
