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
     * @Route("/update/{id}", name="orob2b_attribute_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_attribute_update",
     *      type="entity",
     *      class="OroB2BAttributeBundle:Attribute",
     *      permission="EDIT"
     * )
     * @param \OroB2B\Bundle\AttributeBundle\Entity\Attribute $attribute
     * @return array
     */
    public function updateAction(Attribute $attribute)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $attribute,
            $this->get('orob2b_attribute.form.attribute'),
            function (Attribute $attribute) {
                return array(
                    'route' => 'orob2b_attribute_update',
                    'parameters' => array('id' => $attribute->getId())
                );
            },
            function (Attribute $attribute) {
                return array(
                    'route' => 'orob2b_attribute_view',
                    'parameters' => array('id' => $attribute->getId())
                );
            },
            $this->get('translator')->trans('orob2b.attribute.controller.attribute.saved.message'),
            $this->get('orob2b_attribute.form.handler.attribute')
        );
    }

    /**
     * @Route("/", name="orob2b_attribute_index")
     * @Template
     * @Acl(
     *      id="orob2b_attribute_view",
     *      type="entity",
     *      class="OroB2BAttributeBundle:Attribute",
     *      permission="VIEW"
     * )
     *
     * @return array
     */
    public function indexAction()
    {
        return array(
            'entity_class' => $this->container->getParameter('orob2b_attribute.attribute.entity.class')
        );
    }

    /**
     * @Route("/info/{id}", name="orob2b_attribute_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_attribute_view")
     *
     * @param Attribute $attribute
     * @return array
     */
    public function infoAction(Attribute $attribute)
    {
        return [
            'attribute' => $attribute
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_attribute_view", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_attribute_view")
     *
     * @param Attribute $entity
     * @return array
     */
    public function viewAction(Attribute $entity)
    {
        return [
            'entity' => $entity
        ];
    }
}
