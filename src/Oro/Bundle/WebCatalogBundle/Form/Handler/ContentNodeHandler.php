<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Handler;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;

class ContentNodeHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var RequestStack */
    protected $requestStack;

    /** @var SlugGenerator */
    protected $slugGenerator;

    /** @var ObjectManager */
    protected $manager;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param RequestStack $requestStack
     * @param SlugGenerator $slugGenerator
     * @param ObjectManager $manager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        RequestStack $requestStack,
        SlugGenerator $slugGenerator,
        ObjectManager $manager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->requestStack = $requestStack;
        $this->slugGenerator = $slugGenerator;
        $this->manager = $manager;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    /**
     * @param ContentNode $contentNode
     *
     * @return bool
     */
    public function process(ContentNode $contentNode)
    {
        $event = new FormProcessEvent($this->form, $contentNode);
        $request = $this->requestStack->getCurrentRequest();
        $this->eventDispatcher->dispatch(Events::BEFORE_FORM_DATA_SET, $event);

        if ($event->isFormProcessInterrupted()) {
            return false;
        }

        $this->form->setData($contentNode);

        if ($request->isMethod(Request::METHOD_POST)) {
            $event = new FormProcessEvent($this->form, $contentNode);
            $this->eventDispatcher->dispatch(Events::BEFORE_FORM_SUBMIT, $event);

            if ($event->isFormProcessInterrupted()) {
                return false;
            }

            $this->form->submit($request);
            if ($this->form->isValid()) {
                $this->onSuccess($contentNode);
                
                return true;
            }
        }

        return false;
    }

    /**
     * @param ContentNode $contentNode
     */
    protected function onSuccess(ContentNode $contentNode)
    {
        $this->createDefaultVariantScopes($contentNode);
        $this->slugGenerator->generate($contentNode);
        $this->manager->persist($contentNode);

        $this->eventDispatcher->dispatch(
            Events::BEFORE_FLUSH,
            new AfterFormProcessEvent($this->form, $contentNode)
        );

        $this->manager->flush();

        $this->eventDispatcher->dispatch(
            Events::AFTER_FLUSH,
            new AfterFormProcessEvent($this->form, $contentNode)
        );
    }

    /**
     * @param ContentNode $contentNode
     */
    protected function createDefaultVariantScopes(ContentNode $contentNode)
    {
        $defaultVariant = $contentNode->getDefaultVariant();

        if ($defaultVariant) {
            $defaultVariant->resetScopes();

            $defaultVariantScopes = $this->getDefaultVariantScopes($contentNode);
            foreach ($defaultVariantScopes as $scope) {
                $defaultVariant->addScope($scope);
            }
        }
    }

    /**
     * @param ContentNode $contentNode
     * @return Collection|Scope[]
     */
    protected function getDefaultVariantScopes(ContentNode $contentNode)
    {
        $contentNodeScopes = $contentNode->getScopesConsideringParent();

        $scopes = clone $contentNodeScopes;
        foreach ($contentNode->getContentVariants() as $contentVariant) {
            foreach ($contentVariant->getScopes() as $contentVariantScope) {
                $scopes->removeElement($contentVariantScope);
            }
        }

        return $scopes;
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param FormInterface $form
     */
    public function setForm($form)
    {
        $this->form = $form;
    }
}
