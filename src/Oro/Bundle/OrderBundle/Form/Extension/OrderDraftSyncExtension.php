<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Form\Extension;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\Form\Type\SubOrderType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Synchronizes the order with its order draft if it exists.
 * Creates the order draft when it does not exist yet.
 */
class OrderDraftSyncExtension extends AbstractTypeExtension implements FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    public function __construct(
        private readonly OrderDraftManager $orderDraftManager
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['draft_session_sync']) {
            return;
        }

        if (!$this->orderDraftManager->getDraftSessionUuid()) {
            return;
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, $this->preSetData(...), 100);
        $builder->addEventListener(FormEvents::POST_SET_DATA, $this->postSetData(...), -100);
    }

    /**
     * Synchronizes the order with its order draft if it exists.
     */
    private function preSetData(FormEvent $event): void
    {
        /** @var Order $order */
        $order = $event->getData();

        $this->orderDraftManager->loadFromEntityDraft($order);
    }

    /**
     * Creates the order draft when it does not exist yet.
     */
    private function postSetData(FormEvent $event): void
    {
        /** @var Order $order */
        $order = $event->getData();
        if ($this->orderDraftManager->hasEntityDraft($order)) {
            return;
        }

        $this->orderDraftManager->saveToEntityDraft($order);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('draft_session_sync')
            ->allowedTypes('bool')
            ->default(false)
            ->normalize(fn (Options $options, $value) => $this->isFeaturesEnabled() ? $value : false);
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class, SubOrderType::class];
    }
}
