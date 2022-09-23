<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\Form\EventSubscriber\DigitalAssetTwigTagsEventSubscriber;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
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

    private DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter;

    private ?EventSubscriberInterface $digitalAssetTwigTagsEventSubscriber = null;

    public function __construct(DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter)
    {
        $this->digitalAssetTwigTagsConverter = $digitalAssetTwigTagsConverter;
    }

    public function setDigitalAssetTwigTagsEventSubscriber(
        ?EventSubscriberInterface $digitalAssetTwigTagsEventSubscriber
    ): void {
        $this->digitalAssetTwigTagsEventSubscriber = $digitalAssetTwigTagsEventSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(
            $this->digitalAssetTwigTagsEventSubscriber
            ?? new DigitalAssetTwigTagsEventSubscriber($this->digitalAssetTwigTagsConverter)
        );
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['data-grapesjs-styles'] = $form->getName();
    }

    public function getParent()
    {
        return HiddenType::class;
    }
}
