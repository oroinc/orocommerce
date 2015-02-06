<?php

namespace OroB2B\Bundle\AttributeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Form\Handler\UpdateAttributeHandler;
use OroB2B\Bundle\AttributeBundle\Form\Type\CreateAttributeType;
use OroB2B\Bundle\AttributeBundle\Form\Type\UpdateAttributeType;

class AttributeController extends Controller
{
    /**
     * @Route("/create", name="orob2b_attribute_create")
     * @Template
     * @Acl(
     *      id="orob2b_attribute_create",
     *      type="entity",
     *      class="OroB2BAttributeBundle:Attribute",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        $request = $this->getRequest();
        $initForm = $this->createForm(CreateAttributeType::NAME);
        if ($request->getMethod() == 'POST') {
            $initForm->submit($request);
        }

        return [
            'form' => $initForm->createView()
        ];
    }

    /**
     * @Route("/init", name="orob2b_attribute_init")
     * @Template("OroB2BAttributeBundle:Attribute:update.html.twig")
     * @AclAncestor("orob2b_attribute_create")
     *
     * @return array|RedirectResponse
     */
    public function initAction()
    {
        $request = $this->getRequest();
        if ($request->getMethod() != 'POST') {
            return $this->redirect($this->generateUrl('orob2b_attribute_init'));
        }

        if ($request->request->has(CreateAttributeType::NAME)) {
            // processing of create form
            $form = $this->createForm(CreateAttributeType::NAME);
            $form->submit($request);

            if (!$form->isValid()) {
                return $this->forward('OroB2BAttributeBundle:Attribute:create');
            }

            $attribute = $form->getData();
        } elseif ($request->request->has(UpdateAttributeType::NAME)) {
            // processing of init form
            $formData = $request->request->get(UpdateAttributeType::NAME);
            $attribute = new Attribute();
            $attribute->setCode($formData['code'])
                ->setType($formData['type'])
                ->setLocalized(!empty($formData['localized']));
        } else {
            throw new BadRequestHttpException('Request does not contain attribute data');
        }

        return $this->update($attribute);
    }

    /**
     * Edit product form
     *
     * @Route("/update/{id}", name="orob2b_attribute_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_attribute_update",
     *      type="entity",
     *      class="OroB2BAttributeBundle:Attribute",
     *      permission="EDIT"
     * )
     * @param Attribute $attribute
     * @return array|RedirectResponse
     */
    public function updateAction(Attribute $attribute)
    {
        return $this->update($attribute);
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

    /**
     * @param Attribute $attribute
     * @return array|RedirectResponse
     */
    protected function update(Attribute $attribute)
    {
        $form = $this->createForm(UpdateAttributeType::NAME, $attribute);
        $handler = new UpdateAttributeHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass('OroB2BAttributeBundle:Attribute')
        );

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $attribute,
            $form,
            function (Attribute $attribute) {
                return ['route' => 'orob2b_attribute_update', 'parameters' => ['id' => $attribute->getId()]];
            },
            function () {
                return ['route' => 'orob2b_attribute_index'];
            },
            $this->get('translator')->trans('orob2b.attribute.controller.attribute.saved.message'),
            $handler
        );
    }
}
