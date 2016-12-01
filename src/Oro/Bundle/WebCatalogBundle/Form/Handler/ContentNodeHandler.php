<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Handler;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
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
     * @param FormInterface $form
     * @param Request $request
     * @param SlugGenerator $slugGenerator
     * @param ObjectManager $manager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        SlugGenerator $slugGenerator,
        ObjectManager $manager
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->slugGenerator = $slugGenerator;
        $this->manager = $manager;
    }
    
    /**
     * @param ContentNode $contentNode
     *
     * @return bool
     */
    public function process(ContentNode $contentNode)
    {
        $this->form->setData($contentNode);

        if ($this->request->isMethod(Request::METHOD_POST)) {
            $this->form->submit($this->request);
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
        $this->manager->flush();
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
