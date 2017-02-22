<?php

namespace Oro\Bundle\RedirectBundle\Helper;

use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Oro\Bundle\RedirectBundle\Generator\SlugUrlDiffer;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
     * @param FormFactoryInterface $formFactory
     * @param RequestStack $requestStack
     * @param SlugEntityGenerator $slugGenerator
     * @param SlugUrlDiffer $slugUrlDiffer
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        SlugEntityGenerator $slugGenerator,
        SlugUrlDiffer $slugUrlDiffer
    ) {
        $this->formFactory = $formFactory;
        $this->requestStack = $requestStack;
        $this->slugGenerator = $slugGenerator;
        $this->slugUrlDiffer = $slugUrlDiffer;
    }

    /**
     * @param SluggableInterface $entity
     * @param string $formType
     * @return array
     */
    public function getChangedSlugsData(SluggableInterface $entity, $formType)
    {
        $request = $this->requestStack->getCurrentRequest();

        $oldSlugs = $this->slugGenerator->prepareSlugUrls($entity);

        $form = $this->formFactory->create($formType, $entity);
        $form->submit($request);

        $newSlugs = $this->slugGenerator->prepareSlugUrls($form->getData());

        return $this->slugUrlDiffer->getSlugUrlsChanges($oldSlugs, $newSlugs);
    }
}
