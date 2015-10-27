<?php

namespace OroB2B\Bundle\FrontendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

class FrontendController extends Controller
{
    /**
     * @Route("/", name="_frontend")
     * @return RedirectResponse
     */
    public function indexAction()
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('orob2b_account_account_user_security_login');
        } else {
            return $this->redirectToRoute('orob2b_product_frontend_product_index');
        }
    }

    /**
     * @Route(
     *      "/history/{entity}/{id}/{_format}",
     *      name="orob2b_frontend_dataaudit_history",
     *      requirements={"entity"="[a-zA-Z0-9_]+", "id"="\d+"},
     *      defaults={"entity"="entity", "id"=0, "_format" = "html"}
     * )
     * @Template("OroDataAuditBundle:Audit/widget:history.html.twig")
     * @Acl(
     *      id="orob2b_frontend_dataaudit_history",
     *      type="action",
     *      label="orob2b.frontend.dataaudit.module_label",
     *      group_name=""
     * )
     */
    public function historyAction($entity, $id)
    {
        return array(
            'gridName'     => 'frontend-audit-history-grid',
            'entityClass'  => $entity,
            'entityId'     => $id,
        );
    }
}
