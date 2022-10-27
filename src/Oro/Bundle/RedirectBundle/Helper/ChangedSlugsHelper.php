<?php

namespace Oro\Bundle\RedirectBundle\Helper;

use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Oro\Bundle\RedirectBundle\Generator\SlugUrlDiffer;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Responsible for the slug changing and preparation
 */
class ChangedSlugsHelper
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var SlugEntityGenerator
     */
    private $slugGenerator;

    /**
     * @var SlugUrlDiffer
     */
    private $slugUrlDiffer;

    /**
     * @var DraftHelper
     */
    private $draftHelper;

    public function __construct(
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        SlugEntityGenerator $slugGenerator,
        SlugUrlDiffer $slugUrlDiffer,
        DraftHelper $draftHelper
    ) {
        $this->formFactory = $formFactory;
        $this->requestStack = $requestStack;
        $this->slugGenerator = $slugGenerator;
        $this->slugUrlDiffer = $slugUrlDiffer;
        $this->draftHelper = $draftHelper;
    }

    /**
     * @param SluggableInterface $entity
     * @param string $formType
     * @return array
     */
    public function getChangedSlugsData(SluggableInterface $entity, $formType)
    {
        if ($this->isUnsupportedEntity($entity)) {
            return [];
        }

        $request = $this->requestStack->getCurrentRequest();

        $oldSlugs = $this->slugGenerator->prepareSlugUrls($entity);

        $form = $this->formFactory->create($formType, $entity);
        $form->handleRequest($request);

        $newSlugs = $this->slugGenerator->prepareSlugUrls($form->getData());

        return $this->slugUrlDiffer->getSlugUrlsChanges($oldSlugs, $newSlugs);
    }

    /**
     * @param SluggableInterface $entity
     * @param string $newSlug
     * @return array
     */
    public function getChangedDefaultSlugData(SluggableInterface $entity, $newSlug)
    {
        $oldSlugs = $this->slugGenerator->prepareSlugUrls($entity);

        $entity->setDefaultSlugPrototype($newSlug);
        $newSlugs = $this->slugGenerator->prepareSlugUrls($entity);

        return $this->slugUrlDiffer->getSlugUrlsChanges($oldSlugs, $newSlugs);
    }

    private function isUnsupportedEntity(SluggableInterface $entity): bool
    {
        return ($entity instanceof DraftableInterface && DraftHelper::isDraft($entity))
            || $this->draftHelper->isSaveAsDraftAction();
    }
}
