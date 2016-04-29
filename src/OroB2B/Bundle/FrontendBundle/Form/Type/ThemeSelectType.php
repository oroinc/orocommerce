<?php

namespace OroB2B\Bundle\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class ThemeSelectType extends AbstractType
{
    const NAME = 'orob2b_frontend_theme_select';
    const GROUP = 'commerce';

    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * @var Theme[]
     */
    protected $themes = [];

    /**
     * @param ThemeManager $themeManager
     */
    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('choices', $this->getChoices());
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $metadata = [];
        foreach ($this->getThemes() as $theme) {
            $metadata[$theme->getName()] = [
                'icon' => $theme->getIcon(),
                'logo' => $theme->getLogo(),
                'screenshot' => $theme->getScreenshot(),
                'description' => $theme->getDescription()
            ];
        }
        $view->vars['themes-metadata'] = $metadata;
    }

    /**
     * @return array
     */
    protected function getChoices()
    {
        $choices = [];

        foreach ($this->getThemes() as $theme) {
            $choices[$theme->getName()] = $theme->getLabel();
        }

        return $choices;
    }

    /**
     * @return Theme[]
     */
    protected function getThemes()
    {
        if (!$this->themes) {
            $this->themes = $this->themeManager->getAllThemes(self::GROUP);
        }

        return $this->themes;
    }
}
