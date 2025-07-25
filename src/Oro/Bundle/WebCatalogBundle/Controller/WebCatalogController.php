<?php

namespace Oro\Bundle\WebCatalogBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\UIBundle\Form\Type\TreeMoveType;
use Oro\Bundle\UIBundle\Model\TreeCollection;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\Type\WebCatalogType;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Web catalog controller
 */
class WebCatalogController extends AbstractController
{
    #[Route(path: '/', name: 'oro_web_catalog_index')]
    #[Template]
    #[AclAncestor('oro_web_catalog_view')]
    public function indexAction(): array
    {
        return [
            'entity_class' => WebCatalog::class
        ];
    }

    #[Route(path: '/view/{id}', name: 'oro_web_catalog_view', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_web_catalog_view', type: 'entity', class: WebCatalog::class, permission: 'VIEW')]
    public function viewAction(WebCatalog $webCatalog): array
    {
        return [
            'entity' => $webCatalog
        ];
    }

    #[Route(path: '/create', name: 'oro_web_catalog_create')]
    #[Template('@OroWebCatalog/WebCatalog/update.html.twig')]
    #[Acl(id: 'oro_web_catalog_create', type: 'entity', class: WebCatalog::class, permission: 'CREATE')]
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new WebCatalog());
    }

    #[Route(path: '/update/{id}', name: 'oro_web_catalog_update', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_web_catalog_update', type: 'entity', class: WebCatalog::class, permission: 'EDIT')]
    public function updateAction(WebCatalog $webCatalog): array|RedirectResponse
    {
        return $this->update($webCatalog);
    }

    #[Route(path: '/move/{id}', name: 'oro_web_catalog_move')]
    #[Template]
    #[Acl(id: 'oro_web_catalog_update', type: 'entity', class: WebCatalog::class, permission: 'EDIT')]
    public function moveAction(Request $request, WebCatalog $webCatalog): array
    {
        $handler = $this->container->get(ContentNodeTreeHandler::class);
        $contentNodeRepository = $this->container->get('doctrine')->getRepository(ContentNode::class);

        $root = $handler->getTreeRootByWebCatalog($webCatalog);
        $treeItems = $handler->getTreeItemList($root, true);

        $collection = new TreeCollection();
        $collection->source = array_intersect_key($treeItems, array_flip($request->get('selected', [])));

        $treeData = $handler->createTree($root, true);
        $handler->disableTreeItems($collection->source, $treeData);
        $form = $this->createForm(TreeMoveType::class, $collection, [
            'tree_items' => $treeItems,
            'tree_data' => $treeData,
        ]);

        $responseData = [
            'treeItems' => $treeItems,
            'changed' => [],
        ];

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $currentInsertPosition = count($collection->target->getChildren());
            $createRedirect = (bool)$collection->createRedirect;
            $handler->setCreateRedirect($createRedirect);
            $targetContentNode = $contentNodeRepository->find($collection->target->getKey());

            foreach ($collection->source as $source) {
                if ($createRedirect) {
                    $sourceContentNode = $contentNodeRepository->find($source->getKey());
                    $urlChanges = $this->container->get(SlugGenerator::class)
                        ->getSlugsUrlForMovedNode($targetContentNode, $sourceContentNode);
                }

                $handler->moveNode($source->getKey(), $collection->target->getKey(), $currentInsertPosition);
                $responseData['changed'][] = [
                    'id' => $source->getKey(),
                    'parent' => $collection->target->getKey(),
                    'position' => $currentInsertPosition,
                    'urlChanges' => isset($urlChanges) ? $urlChanges : ''
                ];
                $currentInsertPosition++;
            }

            $responseData['saved'] = true;
        }

        return array_merge($responseData, ['form' => $form->createView()]);
    }

    protected function update(WebCatalog $webCatalog): array|RedirectResponse
    {
        return $this->container->get(UpdateHandlerFacade::class)->update(
            $webCatalog,
            $this->createForm(WebCatalogType::class, $webCatalog),
            $this->container->get(TranslatorInterface::class)
                ->trans('oro.webcatalog.controller.webcatalog.saved.message')
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            ContentNodeTreeHandler::class,
            SlugGenerator::class,
            TranslatorInterface::class,
            UpdateHandlerFacade::class,
            'doctrine' => ManagerRegistry::class
        ]);
    }
}
