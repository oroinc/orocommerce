<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\FormBundle\Form\Type\LinkTargetType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for the ImageSlide entity.
 */
class ImageSlideType extends AbstractType
{
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
                'checkEmptyFile' => true,
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
            LinkTargetType::class,
            [
                'tooltip' => 'oro.cms.imageslide.form.tooltip.display_in_same_window.label',
                'required' => true,
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
                    'elementpath' => true,
                    'resize' => false,
                    'height' => 300,
                ],
            ]
        );

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'setContentWidget']);
    }

    public function setContentWidget(FormEvent $event): void
    {
        $data = $event->getData();
        if (!$data instanceof ImageSlide) {
            return;
        }

        $data->setContentWidget($event->getForm()->getConfig()->getOption('content_widget'));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ImageSlide::class]);
        $resolver->setRequired(['content_widget']);
        $resolver->setAllowedTypes('content_widget', [ContentWidget::class]);
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
