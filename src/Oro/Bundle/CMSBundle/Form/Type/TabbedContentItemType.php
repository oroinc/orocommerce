<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\TabbedContentItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for the TabbedContentItem entity.
 */
class TabbedContentItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'title',
            TextType::class,
            [
                'label' => 'oro.cms.tabbedcontentitem.title.label',
                'tooltip' => 'oro.cms.tabbedcontentitem.title.tooltip',
                'required' => true,
            ]
        )
        ->add(
            'itemOrder',
            IntegerType::class,
            [
                'label' => 'oro.cms.tabbedcontentitem.item_order.label',
                'tooltip' => 'oro.cms.tabbedcontentitem.item_order.tooltip',
                'required' => true,
            ]
        )
        ->add(
            'content',
            WYSIWYGType::class,
            [
                'label' => 'oro.cms.tabbedcontentitem.content.label',
                'tooltip' => 'oro.cms.tabbedcontentitem.content.tooltip',
                'required' => false,
            ]
        );

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'setContentWidget']);
    }

    public function setContentWidget(FormEvent $event): void
    {
        $data = $event->getData();
        if (!$data instanceof TabbedContentItem) {
            return;
        }

        $data->setContentWidget($event->getForm()->getConfig()->getOption('content_widget'));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => TabbedContentItem::class]);
        $resolver->setRequired(['content_widget']);
        $resolver->setAllowedTypes('content_widget', [ContentWidget::class]);
    }

    public function getBlockPrefix(): string
    {
        return 'oro_cms_tabbed_content_item';
    }
}
