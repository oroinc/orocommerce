<?php

namespace Oro\Bundle\VisibilityBundle\Api\Processor;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The base processor to set Scope entity to a visibility entity based on submitted data.
 * This processor does the following:
 * * gets Scope entity based on submitted data
 * * validates that a visibility entity with the same Scope does not exist
 * * sets the Scope entity to the visibility entity
 */
abstract class AbstractSetVisibilityScope implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private WebsiteManager $websiteManager;
    private TranslatorInterface $translator;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        WebsiteManager $websiteManager,
        TranslatorInterface $translator
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->websiteManager = $websiteManager;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if (!$context->getForm()->isValid()) {
            return;
        }

        /** @var VisibilityInterface $entity */
        $entity = $context->getData();
        $scope = $this->getScope($context, $this->getWebsite($context));
        if ($this->isVisibilityExists($context->getClassName(), $entity, $scope)) {
            FormUtil::addNamedFormError(
                $context->getForm(),
                Constraint::CONFLICT,
                $this->translator->trans('oro.visibility.conflict.message', [], 'validators'),
                null,
                Response::HTTP_CONFLICT
            );
        } else {
            $entity->setScope($scope);
        }
    }

    abstract protected function getScope(CustomizeFormDataContext $context, WebsiteInterface $website): Scope;

    abstract protected function getExistingVisibilitySearchCriteria(VisibilityInterface $entity, Scope $scope): array;

    abstract protected function isExistingVisibilityCheckApplicable(VisibilityInterface $entity): bool;

    private function isVisibilityExists(
        string $visibilityEntityClass,
        VisibilityInterface $entity,
        Scope $scope
    ): bool {
        if (!$this->isExistingVisibilityCheckApplicable($entity)) {
            return false;
        }

        $criteria = $this->getExistingVisibilitySearchCriteria($entity, $scope);
        $qb = $this->doctrineHelper->createQueryBuilder($visibilityEntityClass, 'e')
            ->select('e.id');
        foreach ($criteria as $name => $value) {
            $qb->andWhere(sprintf('e.%1$s = :%1$s', $name))->setParameter($name, $value);
        }

        return (bool)$qb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);
    }

    private function getWebsite(CustomizeFormDataContext $context): WebsiteInterface
    {
        $websiteField = $context->findFormField('website');
        if (null === $websiteField) {
            return $this->websiteManager->getDefaultWebsite();
        }

        return $websiteField->getData();
    }
}
