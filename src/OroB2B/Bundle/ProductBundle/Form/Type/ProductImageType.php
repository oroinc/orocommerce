<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage;
use OroB2B\Bundle\ProductBundle\Form\DataTransformer\ProductImageTypesTransformer;

class ProductImageType extends AbstractType
{
    const NAME = 'orob2b_product_image';

    /**
     * @var Theme
     */
    private $theme;

    /**
     * @param ThemeManager $themeManager
     * @param RequestStack $requestStack
     */
    public function __construct(ThemeManager $themeManager, RequestStack $requestStack)
    {
        $this->theme = $themeManager->getTheme($requestStack->getCurrentRequest()->attributes->get('_theme'));
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('image', 'oro_image');

        foreach ($this->getImageTypes() as $imageType => $config) {
            $isRadioButton = $config['max_number'] === 1;

            $builder->add(
                $imageType,
                $isRadioButton ? 'radio' : 'checkbox',
                [
                    'label' => $config['label'],
                    'value' => 1
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'OroB2B\Bundle\ProductBundle\Entity\ProductImage',
            'image_types' => array_keys($this->getImageTypes()),
        ]);

        $resolver->setAllowedTypes('image_types', 'array');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['imageTypes'] = $options['image_types'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return string[]
     */
    private function getImageTypes()
    {
        return $this->theme->getDataByKey('images')['types'];
    }
}
