<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage;

class ProductImageType extends AbstractType
{
    const NAME = 'orob2b_product_image';

    const IMAGE_TYPE_MAIN = 'main';
    const IMAGE_TYPE_ADDITIONAL = 'additional';
    const IMAGE_TYPE_THUMBNAIL = 'thumbnail';

    /**
     * @var string[]
     */
    private static $imageTypes = [
        self::IMAGE_TYPE_MAIN,
        self::IMAGE_TYPE_ADDITIONAL,
        self::IMAGE_TYPE_THUMBNAIL,
    ];

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
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('image', 'oro_image');

        foreach (self::$imageTypes as $imageType) {
            $builder->add(
                $imageType,
                'choice',
                [
                    'choices' => [$imageType],
                    'multiple' => $this->defineMultipleOptionForImageType($imageType),
                    'expanded' => true,
                    'label' => null,
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'OroB2B\Bundle\ProductBundle\Entity\ProductImage'
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param string $imageType
     * @return bool
     */
    private function defineMultipleOptionForImageType($imageType)
    {
        $imagesConfig = $this->theme->getDataByKey('images');

        return $imagesConfig['types'][$imageType]['max_number'] !== 1;
    }
}
