<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Handler;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\WebCatalogBundle\Event\AfterContentNodeProcessEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Event\BeforeContentNodeProcessEvent;
use Oro\Bundle\WebCatalogBundle\Event\Events;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ContentNodeHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var SlugGenerator
     */
    protected $slugGenerator;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param SlugGenerator $slugGenerator
     * @param ObjectManager $manager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        SlugGenerator $slugGenerator,
        ObjectManager $manager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->form = $form;
        $this->request = $request;
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
        $event = new BeforeContentNodeProcessEvent($this->form, $contentNode);
        $this->eventDispatcher->dispatch(Events::BEFORE_FORM_DATA_SET, $event);

        if ($event->isFormProcessInterrupted()) {
            return false;
        }

        $this->form->setData($contentNode);

        if ($this->request->isMethod(Request::METHOD_POST)) {
            $event = new BeforeContentNodeProcessEvent($this->form, $contentNode);
            $this->eventDispatcher->dispatch(Events::BEFORE_FORM_SUBMIT, $event);

            if ($event->isFormProcessInterrupted()) {
                return false;
            }

            $this->form->submit($this->request);
            if ($this->form->isValid()) {
                $this->onSuccess($this->form, $contentNode);
                
                return true;
            }
        }

        return false;
    }

    /**
     * @param ContentNode $contentNode
     * @param FormInterface $form
     */
    protected function onSuccess(FormInterface $form, ContentNode $contentNode)
    {
        $this->createDefaultVariantScopes($contentNode);
        $this->slugGenerator->generate($contentNode);
        $this->manager->persist($contentNode);
        $this->eventDispatcher->dispatch(Events::BEFORE_FLUSH, new AfterContentNodeProcessEvent($form, $contentNode));
        $this->manager->flush();
        $this->eventDispatcher->dispatch(Events::AFTER_FLUSH, new AfterContentNodeProcessEvent($form, $contentNode));
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
}
