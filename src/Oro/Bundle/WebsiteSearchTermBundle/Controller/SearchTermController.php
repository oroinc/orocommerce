<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Form\Type\SearchTermType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD controller for {@see SearchTerm} entity.
 */
class SearchTermController extends AbstractController
{
    #[Route(path: '/', name: 'oro_website_search_term_index')]
    #[Template]
    #[AclAncestor('oro_website_search_term_acl_view')]
    public function indexAction(): array
    {
        return [
            'entity_class' => SearchTerm::class,
        ];
    }

    #[Route(path: '/create', name: 'oro_website_search_term_create')]
    #[Template('@OroWebsiteSearchTerm/SearchTerm/update.html.twig')]
    #[Acl(id: 'oro_website_search_term_acl_create', type: 'entity', class: SearchTerm::class, permission: 'CREATE')]
    public function createAction(Request $request): array|RedirectResponse
    {
        return $this->update(new SearchTerm(), $request);
    }

    #[Route(path: '/view/{id}', name: 'oro_website_search_term_view', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_website_search_term_acl_view', type: 'entity', class: SearchTerm::class, permission: 'VIEW')]
    public function viewAction(SearchTerm $searchTerm): array
    {
        $scopeEntities = $this->container->get(ScopeManager::class)->getScopeEntities('website_search_term');

        return [
            'entity' => $searchTerm,
            'scopeEntities' => array_reverse($scopeEntities),
        ];
    }

    #[Route(path: '/update/{id}', name: 'oro_website_search_term_update', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_website_search_term_acl_update', type: 'entity', class: SearchTerm::class, permission: 'EDIT')]
    public function updateAction(SearchTerm $searchTerm, Request $request): array|RedirectResponse
    {
        return $this->update($searchTerm, $request);
    }

    #[Route(path: '/delete/{id}', name: 'oro_website_search_term_delete', methods: ['DELETE'])]
    #[Acl(id: 'oro_website_search_term_acl_delete', type: 'entity', class: SearchTerm::class, permission: 'DELETE')]
    public function deleteAction(SearchTerm $searchTerm): JsonResponse
    {
        $translator = $this->container->get(TranslatorInterface::class);

        $doctrine = $this->container->get(ManagerRegistry::class);
        $entityManager = $doctrine->getManagerForClass(SearchTerm::class);
        $entityManager->remove($searchTerm);
        $entityManager->flush();

        $message = $translator->trans('oro.websitesearchterm.controller.search_terms.deleted.message');

        return new JsonResponse(['message' => $message, 'successful' => true]);
    }

    private function update(SearchTerm $searchTerm, Request $request): array|RedirectResponse
    {
        $form = $this->createForm(SearchTermType::class, $searchTerm, [
            'validation_groups' => new GroupSequence([
                Constraint::DEFAULT_GROUP,
                $searchTerm->getId() ? 'website_search_term_update' : 'website_search_term_create',
            ]),
        ]);

        $saveMessage = $this->container->get(TranslatorInterface::class)
            ->trans('oro.websitesearchterm.controller.search_terms.saved.message');

        return $this->container->get(UpdateHandlerFacade::class)
            ->update($searchTerm, $form, $saveMessage, $request);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            TranslatorInterface::class,
            UpdateHandlerFacade::class,
            ManagerRegistry::class,
            ScopeManager::class,
        ];
    }
}
