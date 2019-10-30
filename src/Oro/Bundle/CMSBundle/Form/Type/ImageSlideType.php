<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for the ImageSlide entity.
 */
class ImageSlideType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'slideOrder',
            IntegerType::class,
            [
                'label' => 'oro.cms.imageslide.slide_order.label',
                'tooltip' => 'oro.cms.imageslide.form.tooltip.slide_order.label',
                'required' => true,
            ]
        )->add(
            'mainImage',
            ImageType::class,
            [
                'label' => 'oro.cms.imageslide.main_image.label',
                'tooltip' => 'oro.cms.imageslide.form.tooltip.main_image.label',
                'required' => true,
                //'checkEmptyFile' => true,
                'allowDelete' => false,
            ]
        )->add(
            'mediumImage',
            ImageType::class,
            [
                'label' => 'oro.cms.imageslide.medium_image.label',
                'tooltip' => 'oro.cms.imageslide.form.tooltip.medium_image.label',
                'required' => false,
            ]
        )->add(
            'smallImage',
            ImageType::class,
            [
                'label' => 'oro.cms.imageslide.small_image.label',
                'tooltip' => 'oro.cms.imageslide.form.tooltip.small_image.label',
                'required' => false,
            ]
        )->add(
            'url',
            TextType::class,
            [
                'label' => 'oro.cms.imageslide.url.label',
                'tooltip' => 'oro.cms.imageslide.form.tooltip.url.label',
                'required' => true,
            ]
        )->add(
            'displayInSameWindow',
            ChoiceType::class,
            [
                'label' => 'oro.cms.imageslide.display_in_same_window.label',
                'tooltip' => 'oro.cms.imageslide.form.tooltip.display_in_same_window.label',
                'required' => true,
                'placeholder' => false,
                'choices' => [
                    'oro.cms.imageslide.display_in_same_window.value.yes' => 1,
                    'oro.cms.imageslide.display_in_same_window.value.no' => 0,

                ],
            ]
        )->add(
            'title',
            TextType::class,
            [
                'label' => 'oro.cms.imageslide.title.label',
                'tooltip' => 'oro.cms.imageslide.form.tooltip.title.label',
                'required' => true,
            ]
        )->add(
            'textAlignment',
            ChoiceType::class,
            [
                'label' => 'oro.cms.imageslide.text_alignment.label',
                'tooltip' => 'oro.cms.imageslide.form.tooltip.text_alignment.label',
                'required' => true,
                'placeholder' => false,
                'choices' => $this->getTextAlignmentOptions(),
            ]
        )->add(
            'text',
            OroRichTextType::class,
            [
                'label' => 'oro.cms.imageslide.text.label',
                'tooltip' => 'oro.cms.imageslide.form.tooltip.text.label',
                'required' => false,
                'attr' => [
                    'class' => 'image-slide-text'
                ],
                'wysiwyg_options' => [
                    'statusbar' => true,
                    'resize' => false,
                    'width' => 600,
                    'height' => 298,
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ImageSlide::class]);
    }

    /**
     * @return array
     */
    private function getTextAlignmentOptions()
    {
        return [
            'oro.cms.imageslide.text_alignment.value.center' => ImageSlide::TEXT_ALIGNMENT_CENTER,
            'oro.cms.imageslide.text_alignment.value.left' => ImageSlide::TEXT_ALIGNMENT_LEFT,
            'oro.cms.imageslide.text_alignment.value.right' => ImageSlide::TEXT_ALIGNMENT_RIGHT,
            'oro.cms.imageslide.text_alignment.value.top_left' => ImageSlide::TEXT_ALIGNMENT_TOP_LEFT,
            'oro.cms.imageslide.text_alignment.value.top_center' => ImageSlide::TEXT_ALIGNMENT_TOP_CENTER,
            'oro.cms.imageslide.text_alignment.value.top_right' => ImageSlide::TEXT_ALIGNMENT_TOP_RIGHT,
            'oro.cms.imageslide.text_alignment.value.bottom_left' => ImageSlide::TEXT_ALIGNMENT_BOTTOM_LEFT,
            'oro.cms.imageslide.text_alignment.value.bottom_center' => ImageSlide::TEXT_ALIGNMENT_BOTTOM_CENTER,
            'oro.cms.imageslide.text_alignment.value.bottom_right' => ImageSlide::TEXT_ALIGNMENT_BOTTOM_RIGHT,
        ];
    }
}
