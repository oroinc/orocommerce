<?php

namespace Oro\Bundle\RFPBundle\Form\Extension\Frontend;

use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductType;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Deletes request line item product if it's invisible.
 */
class RequestProductExtension extends AbstractTypeExtension
{
    public function __construct(private ResolvedProductVisibilityProvider $productVisibilityProvider)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData() ?: [];
        $productId = $data['product'] ?? null;
        if ($productId && !$this->productVisibilityProvider->isVisible((int) $productId)) {
            $event->setData(array_replace($data, ['product' => null]));
        }
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [RequestProductType::class];
    }
}
