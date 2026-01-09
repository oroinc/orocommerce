<?php

namespace Oro\Bundle\WebCatalogBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\RedirectBundle\Generator\SlugUrlDiffer;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\ContentNodeFormTemplateDataProvider;
use Oro\Bundle\WebCatalogBundle\Form\Handler\ContentNodeHandler;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeType;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Oro\Component\Tree\Handler\AbstractTreeHandler;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles logic of create, update and move actions for Content Node
 */
class ContentNodeController extends AbstractController
{
    #[Route(path: '/root/{id}', name: 'oro_content_node_update_root', requirements: ['id' => '\d+'])]
    #[Template('@OroWebCatalog/ContentNode/update.html.twig')]
    #[AclAncestor('oro_web_catalog_update')]
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

    #[Route(path: '/create/parent/{id}', name: 'oro_content_node_create', requirements: ['id' => '\d+'])]
    #[Template('@OroWebCatalog/ContentNode/update.html.twig')]
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

    #[Route(path: '/update/{id}', name: 'oro_content_node_update', requirements: ['id' => '\d+'])]
    #[Template('@OroWebCatalog/ContentNode/update.html.twig')]
    public function updateAction(ContentNode $contentNode): array|RedirectResponse
    {
        if (!$this->isGranted('oro_web_catalog_update', $contentNode->getWebCatalog())) {
            throw $this->createAccessDeniedException();
        }

        return $this->updateTreeNode($contentNode);
    }

    #[Route(path: '/move', name: 'oro_content_node_move', methods: ['PUT'])]
    #[AclAncestor('oro_web_catalog_update')]
    #[CsrfProtection()]
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

    #[Route(
        path: '/get-possible-urls/{id}/{newParentId}',
        name: 'oro_content_node_get_possible_urls',
        requirements: ['id' => '\d+', 'newParentId' => '\d+']
    )]
    public function getPossibleUrlsAction(
        ContentNode $contentNode,
        #[MapEntity(id: 'newParentId')]
        ContentNode $newParentContentNode
    ): JsonResponse {
        if (
            !$this->isGranted('oro_web_catalog_update', $contentNode->getWebCatalog())
            || !$this->isGranted('oro_web_catalog_update', $newParentContentNode->getWebCatalog())
        ) {
            throw $this->createAccessDeniedException();
        }

        $slugGenerator = $this->container->get(SlugGenerator::class);

        return new JsonResponse($slugGenerator->getSlugsUrlForMovedNode($newParentContentNode, $contentNode));
    }

    #[Route(path: '/get-changed-urls/{id}', name: 'oro_content_node_get_changed_urls', requirements: ['id' => '\d+'])]
    public function getChangedUrlsAction(ContentNode $node, Request $request): JsonResponse
    {
        if (!$this->isGranted('oro_web_catalog_update', $node->getWebCatalog())) {
            throw $this->createAccessDeniedException();
        }

        $slugGenerator = $this->container->get(SlugGenerator::class);
        $oldUrls = $slugGenerator->prepareSlugUrls($node);

        $form = $this->createForm(ContentNodeType::class, $node);
        $form->submit($request->request->all($form->getName()), false);

        $newUrls = $slugGenerator->prepareSlugUrls($form->getData());

        $slugUrlDiffer = $this->container->get(SlugUrlDiffer::class);

        $urlChanges = $slugUrlDiffer->getSlugUrlsChanges($oldUrls, $newUrls);

        return new JsonResponse($urlChanges);
    }

    protected function updateTreeNode(ContentNode $node): array|RedirectResponse
    {
        return $this->container->get(UpdateHandlerFacade::class)->update(
            $node,
            $this->createForm(ContentNodeType::class, $node),
            $this->container->get(TranslatorInterface::class)
                ->trans('oro.webcatalog.controller.contentnode.saved.message'),
            null,
            $this->container->get(ContentNodeHandler::class),
            $this->container->get(ContentNodeFormTemplateDataProvider::class)
        );
    }

    protected function getTreeHandler(): ContentNodeTreeHandler
    {
        return $this->container->get(ContentNodeTreeHandler::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            SlugGenerator::class,
            SlugUrlDiffer::class,
            ContentNodeTreeHandler::class,
            UpdateHandlerFacade::class,
            TranslatorInterface::class,
            ContentNodeHandler::class,
            ContentNodeFormTemplateDataProvider::class,
            'doctrine' => ManagerRegistry::class
        ]);
    }

    private function getSlugPrototypeStrings(int $nodeId): array
    {
        /** @var ContentNode $movedNode */
        $movedNode = $this->container->get('doctrine')
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
