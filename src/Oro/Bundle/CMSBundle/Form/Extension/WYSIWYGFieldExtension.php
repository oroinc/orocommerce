<?php

namespace Oro\Bundle\CMSBundle\Form\Extension;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType as DBALWYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType as DBALWYSIWYGStyleType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGStylesType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * This extension add WYSIWYGStyleType and WYSIWYGPropertiesType
 */
class WYSIWYGFieldExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    public function onPreSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        $parentForm = $form->getParent();
        if ($parentForm) {
            $formName = $form->getName();
            $parentForm->add($formName . DBALWYSIWYGStyleType::TYPE_SUFFIX, WYSIWYGStylesType::class);
            $parentForm->add($formName . DBALWYSIWYGPropertiesType::TYPE_SUFFIX, WYSIWYGPropertiesType::class);
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
