<?php

namespace Oro\Bundle\CMSBundle\Controller;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Form\Type\ContentBlockType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * CMS Content Block Controller
 */
class ContentBlockController extends AbstractController
{
    /**
     * @Route("/", name="oro_cms_content_block_index")
     * @Template
     * @AclAncestor("oro_cms_content_block_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => ContentBlock::class
        ];
    }

    /**
     * @param ContentBlock $contentBlock
     *
     * @return array
     *
     * @Route("/{id}", name="oro_cms_content_block_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_cms_content_block_view",
     *      type="entity",
     *      class="OroCMSBundle:ContentBlock",
     *      permission="VIEW"
     * )
     */
    public function viewAction(ContentBlock $contentBlock)
    {
        $scopeEntities = $this->get('oro_scope.scope_manager')->getScopeEntities('cms_content_block');

        return [
            'entity' => $contentBlock,
            'scopeEntities' => array_reverse($scopeEntities)
        ];
    }

    /**
     * @return array|RedirectResponse
     * @Route("/create", name="oro_cms_content_block_create")
     * @Template("OroCMSBundle:ContentBlock:update.html.twig")
     * @Acl(
     *      id="oro_cms_content_block_create",
     *      type="entity",
     *      class="OroCMSBundle:ContentBlock",
     *      permission="CREATE"
     * )
     */
    public function createAction()
    {
        $contentBlock = new ContentBlock();

        return $this->update($contentBlock);
    }

    /**
     * @param ContentBlock $contentBlock
     * @return array
     * @Route("/update/{id}", name="oro_cms_content_block_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_cms_content_block_update",
     *      type="entity",
     *      class="OroCMSBundle:ContentBlock",
     *      permission="EDIT"
     * )
     */
    public function updateAction(ContentBlock $contentBlock)
    {
        return $this->update($contentBlock);
    }

    /**
     * @param ContentBlock $contentBlock
     * @return array|RedirectResponse
     */
    protected function update(ContentBlock $contentBlock)
    {
        $form = $this->createForm(ContentBlockType::class, $contentBlock);

        return $this->get('oro_form.model.update_handler')->update(
            $contentBlock,
            $form,
            $this->get('translator')->trans('oro.cms.controller.contentblock.saved.message')
        );
    }
}
