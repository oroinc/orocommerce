<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Provides styles for WYSIWYG editor.
 */
class WYSIWYGStylesType extends AbstractType
{
    public const TYPE_SUFFIX = WYSIWYGStyleType::TYPE_SUFFIX;

    private EventSubscriberInterface $digitalAssetTwigTagsEventSubscriber;

    public function __construct(EventSubscriberInterface $digitalAssetTwigTagsEventSubscriber)
    {
        $this->digitalAssetTwigTagsEventSubscriber = $digitalAssetTwigTagsEventSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber($this->digitalAssetTwigTagsEventSubscriber);
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['attr']['data-grapesjs-styles'] = $form->getName();
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }
}
