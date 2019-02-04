<?php

namespace Oro\Bundle\ConsentBundle\Form\Extension;

use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ConsentBundle\Event\DeclinedConsentsEvent;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentAcceptanceType;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedConsents;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPages;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendCustomerUserProfileType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Extends FrontendCustomerUserProfileType with consents field
 */
class FrontendCustomerUserProfileExtension extends AbstractTypeExtension implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $constraints = [
            new RemovedLandingPages(),
            new RemovedConsents(),
        ];

        $builder->add(
            ConsentAcceptanceType::TARGET_FIELDNAME,
            ConsentAcceptanceType::class,
            [
                'constraints' => $constraints
            ]
        );

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    /**
     * @return string
     */
    public function getExtendedType()
    {
        return FrontendCustomerUserProfileType::class;
    }

    /**
     * Retrieves declined consents and throws DeclinedConsentsEvent
     *
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event): void
    {
        $customerUser = $event->getData();
        if (!$customerUser instanceof CustomerUser) {
            return;
        }

        $acceptedConsents = $customerUser->getAcceptedConsents();
        if (!$acceptedConsents instanceof PersistentCollection) {
            return;
        }

        $declinedConsents = $acceptedConsents->getDeleteDiff();
        if (empty($declinedConsents)) {
            return;
        }

        $this->eventDispatcher->dispatch(
            DeclinedConsentsEvent::EVENT_NAME,
            new DeclinedConsentsEvent($declinedConsents, $customerUser)
        );
    }
}
