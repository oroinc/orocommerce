<?php

namespace Oro\Bundle\ConsentBundle\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that all checked consents exist in the database
 */
class RemovedConsentsValidator extends ConstraintValidator
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     ** @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_array($value) && !$value instanceof ArrayCollection) {
            throw new \LogicException("Incorrect type of the value!");
        }

        $value = $value->toArray();

        $checkedConsentIds = array_map(function (ConsentAcceptance $consentAcceptance) {
            return $consentAcceptance->getConsent()->getId();
        }, $value);

        if (empty($checkedConsentIds)) {
            return;
        }
        /** @var ConsentRepository $consentRepository */
        $consentRepository = $this->doctrineHelper->getEntityRepository(Consent::class);
        $nonExistentConsentIds = $consentRepository->getNonExistentConsentIds($checkedConsentIds);

        if (!empty($nonExistentConsentIds)) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
