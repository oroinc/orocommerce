<?php

namespace Oro\Bundle\WebCatalogBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\RedirectBundle\Generator\SlugUrlDiffer;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\ContentNodeFormTemplateDataProvider;
use Oro\Bundle\WebCatalogBundle\Form\Handler\ContentNodeHandler;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeType;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Oro\Component\Tree\Handler\AbstractTreeHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles logic of create, update and move actions for Content Node
 */
class ContentNodeController extends AbstractController
{
    /**
     * @Route("/root/{id}", name="oro_content_node_update_root", requirements={"id"="\d+"})
     * @AclAncestor("oro_web_catalog_update")
     * @Template("@OroWebCatalog/ContentNode/update.html.twig")
     */
    public function createRootAction(WebCatalog $webCatalog): array|RedirectResponse
    {
        $rootNode = $this->getTreeHandler()->getTreeRootByWebCatalog($webCatalog);
        if (!$rootNode) {
            $rootNode = new ContentNode();
            $rootNode->setWebCatalog($webCatalog);
        }

        $rootNode->setUpdatedAt(new \DateTime());

        return $this->updateTreeNode($rootNode);
    }

    /**
     * @Route("/create/parent/{id}", name="oro_content_node_create", requirements={"id"="\d+"})
     * @Template("@OroWebCatalog/ContentNode/update.html.twig")
     */
    public function createAction(ContentNode $parentNode): array|RedirectResponse
    {
        if (!$this->isGranted('oro_web_catalog_update', $parentNode->getWebCatalog())) {
            throw $this->createAccessDeniedException();
        }

        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($parentNode->getWebCatalog());
        $contentNode->setParentNode($parentNode);

        return $this->updateTreeNode($contentNode);
    }

    /**
     * @Route("/update/{id}", name="oro_content_node_update", requirements={"id"="\d+"})
     * @Template("@OroWebCatalog/ContentNode/update.html.twig")
     */
    public function updateAction(ContentNode $contentNode): array|RedirectResponse
    {
        if (!$this->isGranted('oro_web_catalog_update', $contentNode->getWebCatalog())) {
            throw $this->createAccessDeniedException();
        }

        return $this->updateTreeNode($contentNode);
    }

    /**
     * @Route("/move", name="oro_content_node_move", methods={"PUT"})
     * @CsrfProtection()
     * @AclAncestor("oro_web_catalog_update")
     */
    public function moveAction(Request $request): JsonResponse
    {
        $nodeId = (int)$request->get('id');
        $parentId = (int)$request->get('parent');
        $position = (int)$request->get('position');
        $createRedirect = (bool)$request->get('createRedirect', false);

        $handler = $this->getTreeHandler();
        $handler->setCreateRedirect($createRedirect);

        $responseData = $handler->moveNode($nodeId, $parentId, $position);

        if ($responseData['status'] === AbstractTreeHandler::SUCCESS_STATUS) {
            $responseData['slugPrototypes'] = $this->getSlugPrototypeStrings($nodeId);
        }

        return new JsonResponse($responseData);
    }

    /**
     * @Route(
     *     "/get-possible-urls/{id}/{newParentId}",
     *     name="oro_content_node_get_possible_urls",
     *     requirements={"id"="\d+", "newParentId"="\d+"}
     * )
     * @ParamConverter("newParentContentNode", options={"id" = "newParentId"})
     */
    public function getPossibleUrlsAction(ContentNode $contentNode, ContentNode $newParentContentNode): JsonResponse
    {
        if (!$this->isGranted('oro_web_catalog_update', $contentNode->getWebCatalog())
            || !$this->isGranted('oro_web_catalog_update', $newParentContentNode->getWebCatalog())) {
            throw $this->createAccessDeniedException();
        }

        $slugGenerator = $this->get(SlugGenerator::class);

        return new JsonResponse($slugGenerator->getSlugsUrlForMovedNode($newParentContentNode, $contentNode));
    }

    /**
     * @Route(
     *     "/get-changed-urls/{id}",
     *     name="oro_content_node_get_changed_urls",
     *     requirements={"id"="\d+"}
     * )
     */
    public function getChangedUrlsAction(ContentNode $node, Request $request): JsonResponse
    {
        if (!$this->isGranted('oro_web_catalog_update', $node->getWebCatalog())) {
            throw $this->createAccessDeniedException();
        }

        $slugGenerator = $this->get(SlugGenerator::class);
        $oldUrls = $slugGenerator->prepareSlugUrls($node);

        $form = $this->createForm(ContentNodeType::class, $node);
        $form->submit($request->request->get($form->getName()), false);

        $newUrls = $slugGenerator->prepareSlugUrls($form->getData());

        $slugUrlDiffer = $this->get(SlugUrlDiffer::class);

        $urlChanges = $slugUrlDiffer->getSlugUrlsChanges($oldUrls, $newUrls);

        return new JsonResponse($urlChanges);
    }

    protected function updateTreeNode(ContentNode $node): array|RedirectResponse
    {
        return $this->get(UpdateHandlerFacade::class)->update(
            $node,
            $this->createForm(ContentNodeType::class, $node),
            $this->get(TranslatorInterface::class)->trans('oro.webcatalog.controller.contentnode.saved.message'),
            null,
            $this->get(ContentNodeHandler::class),
            $this->get(ContentNodeFormTemplateDataProvider::class)
        );
    }

    protected function getTreeHandler(): ContentNodeTreeHandler
    {
        return $this->get(ContentNodeTreeHandler::class);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            SlugGenerator::class,
            SlugUrlDiffer::class,
            ContentNodeTreeHandler::class,
            UpdateHandlerFacade::class,
            TranslatorInterface::class,
            ContentNodeHandler::class,
            ContentNodeFormTemplateDataProvider::class
        ]);
    }

    private function getSlugPrototypeStrings(int $nodeId): array
    {
        /** @var ContentNode $movedNode */
        $movedNode = $this->getDoctrine()
            ->getManagerForClass(ContentNode::class)
            ->find(ContentNode::class, $nodeId);

        $slugPrototypes = [];
        foreach ($movedNode->getSlugPrototypes() as $slugPrototype) {
            $localizationKey = 'default';
            if ($slugPrototype->getLocalization()) {
                $localizationKey = $slugPrototype->getLocalization()->getId();
            }
            $slugPrototypes[$localizationKey] = $slugPrototype->getString();
        }

        return $slugPrototypes;
    }
}
