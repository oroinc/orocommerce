<?php

namespace Oro\Bundle\ConsentBundle\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Entity\Repository\PageRepository;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that all landing pages that uses in checked consents exist in the database
 */
class RemovedLandingPagesValidator extends ConstraintValidator
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
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
        if (!$value instanceof ArrayCollection) {
            throw new \LogicException("Incorrect type of the value!");
        }

        $checkedLandingPageIds = [];

        /** @var ConsentAcceptance $consentAcceptance */
        foreach ($value as $consentAcceptance) {
            if ($consentAcceptance->getLandingPage()) {
                $checkedLandingPageIds[] = $consentAcceptance->getLandingPage()->getId();
            }
        }

        if (empty($checkedLandingPageIds)) {
            return;
        }

        /** @var PageRepository $pageRepository */
        $pageRepository = $this->doctrineHelper->getEntityRepository(Page::class);
        $nonExistentPageIds = $pageRepository->getNonExistentPageIds($checkedLandingPageIds);
        if (!empty($nonExistentPageIds)) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
