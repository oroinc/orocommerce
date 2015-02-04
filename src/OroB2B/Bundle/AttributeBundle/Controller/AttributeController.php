<?php

namespace OroB2B\Bundle\AttributeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Form\Type\InitAttributeType;

class AttributeController extends Controller
{
    /**
     * @Route("/create", name="orob2b_attribute_create")
     * @Acl(
     *      id="orob2b_attribute_create",
     *      type="entity",
     *      class="OroB2BAttributeBundle:Attribute",
     *      permission="CREATE"
     * )
     */
    public function createAction()
    {
        $request = $this->getRequest();
        if ($request->getMethod() != 'POST') {
            return $this->redirect($this->generateUrl('orob2b_attribute_init'));
        }

        $attribute = new Attribute();

        // processing of init form
        if ($request->request->has(InitAttributeType::NAME)) {
            $initForm = $this->createForm(InitAttributeType::NAME);
            $initForm->submit($request);

            // just in case if frontend validation will not work
            if (!$initForm->isValid()) {
                return $this->forward('OroB2BAttributeBundle:Attribute:init');
            }

            $attribute->setCode($initForm->get('code')->getData())
                ->setType($initForm->get('type')->getData())
                ->setLocalized($initForm->get('localized')->getData());
        }

        return new Response('create attribute');
    }

    /**
     * @Route("/init", name="orob2b_attribute_init")
     * @Template
     * @AclAncestor("orob2b_attribute_create")
     */
    public function initAction()
    {
        $request = $this->getRequest();
        $initForm = $this->createForm(InitAttributeType::NAME);
        if ($request->getMethod() == 'POST') {
            $initForm->submit($request);
        }

        return [
            'form' => $initForm->createView()
        ];
    }

    /**
     * @Route("/", name="orob2b_attribute_index")
     * @Acl(
     *      id="orob2b_attribute_view",
     *      type="entity",
     *      class="OroB2BAttributeBundle:Attribute",
     *      permission="VIEW"
     * )
     */
    public function indexAction()
    {
        return new Response('list of attributes');
    }
}
