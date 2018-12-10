<?php

namespace Oro\Bundle\ConsentBundle\Form\Extension;

use Oro\Bundle\ConsentBundle\Form\EventSubscriber\CustomerConsentsEventSubscriber;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\FillConsentContextEventSubscriber;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\PopulateFieldCustomerConsentsSubscriber;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedConsents;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPages;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Form extension on oro_customer_frontend_customer_user that adds customer consents
 * in case customer edits their own settings
 */
class FrontendCustomerEditExtension extends AbstractTypeExtension implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /** @var string */
    private $extendedType;

    /** @var CustomerConsentsEventSubscriber */
    private $saveConsentAcceptanceSubscriber;

    /** @var PopulateFieldCustomerConsentsSubscriber */
    private $populateFieldCustomerConsentsSubscriber;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /**
     * @param CustomerConsentsEventSubscriber $saveConsentAcceptanceSubscriber
     * @param PopulateFieldCustomerConsentsSubscriber $populateFieldCustomerConsentsSubscriber
     * @param TokenStorageInterface                   $tokenStorage
     */
    public function __construct(
        CustomerConsentsEventSubscriber $saveConsentAcceptanceSubscriber,
        PopulateFieldCustomerConsentsSubscriber $populateFieldCustomerConsentsSubscriber,
        TokenStorageInterface $tokenStorage
    ) {
        $this->saveConsentAcceptanceSubscriber = $saveConsentAcceptanceSubscriber;
        $this->populateFieldCustomerConsentsSubscriber = $populateFieldCustomerConsentsSubscriber;
        $this->tokenStorage = $tokenStorage;
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

        $builder->addEventSubscriber($this->saveConsentAcceptanceSubscriber);
        $builder->addEventSubscriber($this->populateFieldCustomerConsentsSubscriber);

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            [$this, 'addCustomerConsentsField'],
            2000
        );
    }

    /**
     * @param FormEvent $event
     */
    public function addCustomerConsentsField(FormEvent $event)
    {
        $customerUser = $event->getData();
        if ($customerUser instanceof CustomerUser && $customerUser === $this->getCustomerUser()) {
            $event->getForm()->add(
                CustomerConsentsType::TARGET_FIELDNAME,
                CustomerConsentsType::class,
                [
                    'constraints' => [
                        new RemovedLandingPages(),
                        new RemovedConsents()
                    ]
                ]
            );
        }
    }

    /**
     * @param string $extendedType
     */
    public function setExtendedType($extendedType)
    {
        $this->extendedType = $extendedType;
    }

    /**
     * @return string
     */
    public function getExtendedType()
    {
        return $this->extendedType;
    }

    /**
     * @return CustomerUser|null
     */
    private function getCustomerUser()
    {
        $token = $this->tokenStorage->getToken();
        if ($token && ($user = $token->getUser()) instanceof CustomerUser) {
            return $user;
        }

        return null;
    }
}
