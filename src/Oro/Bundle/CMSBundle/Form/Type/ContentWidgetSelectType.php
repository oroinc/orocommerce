<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\FormBundle\Form\Type\Select2EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type to select a content widget.
 */
class ContentWidgetSelectType extends AbstractType
{
    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => ContentWidget::class,
            'placeholder' => 'oro.cms.contentwidget.form.choose_content_widget.label',
            'expanded' => false,
            'multiple' => false,
            'choice_translation_domain' => false,
            'choice_value' => 'id',
            'widgetTypes' => [],
            'query_builder' => function (Options $options) {
                $repository = $this->registry->getRepository(ContentWidget::class);
                $queryBuilder = $repository->createQueryBuilder('cw');

                if (!empty($options['widgetTypes'])) {
                    $queryBuilder
                        ->where('cw.widgetType IN (:widgetTypes)')
                        ->setParameter('widgetTypes', $options['widgetTypes']);
                }

                return $queryBuilder;
            },
            'choice_label' => function (?ContentWidget $contentWidget) {
                return $contentWidget?->getName();
            },
        ]);

        $resolver->setAllowedTypes('widgetTypes', ['array']);
    }

    #[\Override]
    public function getParent(): string
    {
        return Select2EntityType::class;
    }
}
