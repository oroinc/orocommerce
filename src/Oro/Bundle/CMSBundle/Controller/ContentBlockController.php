<?php

namespace Oro\Bundle\CMSBundle\Controller;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Form\Type\ContentBlockType;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CMS Content Block Controller
 */
class ContentBlockController extends AbstractController
{
    /**
     * @Route("/", name="oro_cms_content_block_index")
     * @Template
     * @AclAncestor("oro_cms_content_block_view")
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => ContentBlock::class
        ];
    }

    /**
     * @Route("/{id}", name="oro_cms_content_block_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_cms_content_block_view",
     *      type="entity",
     *      class="OroCMSBundle:ContentBlock",
     *      permission="VIEW"
     * )
     */
    public function viewAction(ContentBlock $contentBlock): array
    {
        $scopeEntities = $this->get(ScopeManager::class)->getScopeEntities('cms_content_block');

        return [
            'entity' => $contentBlock,
            'scopeEntities' => array_reverse($scopeEntities)
        ];
    }

    /**
     * @Route("/create", name="oro_cms_content_block_create")
     * @Template("@OroCMS/ContentBlock/update.html.twig")
     * @Acl(
     *      id="oro_cms_content_block_create",
     *      type="entity",
     *      class="OroCMSBundle:ContentBlock",
     *      permission="CREATE"
     * )
     */
    public function createAction(): array|RedirectResponse
    {
        $contentBlock = new ContentBlock();

        return $this->update($contentBlock);
    }

    /**
     * @Route("/update/{id}", name="oro_cms_content_block_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_cms_content_block_update",
     *      type="entity",
     *      class="OroCMSBundle:ContentBlock",
     *      permission="EDIT"
     * )
     */
    public function updateAction(ContentBlock $contentBlock): array|RedirectResponse
    {
        return $this->update($contentBlock);
    }

    protected function update(ContentBlock $contentBlock): array|RedirectResponse
    {
        $form = $this->createForm(ContentBlockType::class, $contentBlock);

        return $this->get(UpdateHandlerFacade::class)->update(
            $contentBlock,
            $form,
            $this->get(TranslatorInterface::class)->trans('oro.cms.controller.contentblock.saved.message')
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                ScopeManager::class,
                UpdateHandlerFacade::class
            ]
        );
    }
}
