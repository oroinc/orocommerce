<?php

namespace Oro\Bundle\ConsentBundle\Form\Extension;

use Oro\Bundle\ConsentBundle\Form\EventSubscriber\CustomerConsentsEventSubscriber;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\GuestCustomerConsentsEventSubscriber;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\PopulateFieldCustomerConsentsSubscriber;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedConsents;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPages;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RequiredConsents;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Form extension on oro_rfp_frontend_request that adds non accepted mandatory customer consents
 * to the create RFQ form
 */
class FrontendRfqExtension extends AbstractTypeExtension implements FeatureToggleableInterface
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

    /** @var GuestCustomerConsentsEventSubscriber */
    private $guestCustomerConsentsEventSubscriber;

    /**
     * @param CustomerConsentsEventSubscriber $saveConsentAcceptanceSubscriber
     * @param GuestCustomerConsentsEventSubscriber $guestCustomerConsentsEventSubscriber
     * @param PopulateFieldCustomerConsentsSubscriber $populateFieldCustomerConsentsSubscriber
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        CustomerConsentsEventSubscriber $saveConsentAcceptanceSubscriber,
        GuestCustomerConsentsEventSubscriber $guestCustomerConsentsEventSubscriber,
        PopulateFieldCustomerConsentsSubscriber $populateFieldCustomerConsentsSubscriber,
        TokenStorageInterface $tokenStorage
    ) {
        $this->saveConsentAcceptanceSubscriber = $saveConsentAcceptanceSubscriber;
        $this->guestCustomerConsentsEventSubscriber = $guestCustomerConsentsEventSubscriber;
        $this->populateFieldCustomerConsentsSubscriber = $populateFieldCustomerConsentsSubscriber;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        if ($this->isGuestCustomerUser()) {
            $builder->addEventSubscriber($this->guestCustomerConsentsEventSubscriber);
        } else {
            $builder->addEventSubscriber($this->saveConsentAcceptanceSubscriber);
            $builder->addEventSubscriber($this->populateFieldCustomerConsentsSubscriber);
        }

        $builder->add(
            CustomerConsentsType::TARGET_FIELDNAME,
            CustomerConsentsType::class,
            [
                'constraints' => [
                    new RemovedLandingPages(),
                    new RemovedConsents(),
                    new RequiredConsents()
                ]
            ]
        );
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
     * @return bool
     */
    private function isGuestCustomerUser()
    {
        return $this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken;
    }
}
