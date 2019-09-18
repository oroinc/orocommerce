<?php

namespace Oro\Bundle\CMSBundle\Form\Extension;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType as DBALWYSIWYGStyleType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGStylesType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * This extension add WYSIWYGStyleType
 */
class WYSIWYGStylesExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        $parentForm = $form->getParent();
        if ($parentForm) {
            $parentForm->add($form->getName() . DBALWYSIWYGStyleType::TYPE_SUFFIX, WYSIWYGStylesType::class);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [WYSIWYGType::class];
    }
}
