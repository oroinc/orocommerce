<?php

namespace Oro\Bundle\CMSBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Form\Type\ContentBlockType;
use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

class ContentBlockController extends Controller
{
    /**
     * @Route("/", name="oro_cms_content_block_index")
     * @Template
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
     *      id="oro_cms_contentblock_view",
     *      type="entity",
     *      class="OroCMSBundle:ContentBlock",
     *      permission="VIEW"
     * )
     */
    public function viewAction(ContentBlock $contentBlock)
    {
        return ['entity' => $contentBlock];
    }

    /**
     * @return array|RedirectResponse
     *
     * @Route("/create", name="oro_cms_content_block_create")
     * @Template("OroCMSBundle:ContentBlock:update.html.twig")
     * @Acl(
     *      id="oro_cms_contentblock_create",
     *      type="entity",
     *      class="OroCMSBundle:ContentBlock",
     *      permission="CREATE"
     * )
     */
    public function createAction()
    {
        return $this->update(new ContentBlock());
    }

    /**
     * @param ContentBlock $contentBlock
     *
     * @return array
     *
     * @Route("/update/{id}", name="oro_cms_content_block_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_cms_contentblock_update",
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
     *
     * @return array|RedirectResponse
     */
    protected function update(ContentBlock $contentBlock)
    {
        return $this->get('oro_form.model.update_handler')->update(
            $contentBlock,
            $this->createForm(ContentBlockType::NAME, $contentBlock),
            $this->get('translator')->trans('oro_cms.controller.contentblock.saved.message')
        );
    }
}
