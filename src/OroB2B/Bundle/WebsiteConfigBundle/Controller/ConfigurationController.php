<?php

namespace OroB2B\Bundle\WebsiteConfigBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class ConfigurationController extends Controller
{
    /**
     * @Route(
     *      "/website/{id}/{activeGroup}/{activeSubGroup}",
     *      name="orob2b_website_config",
     *      requirements={"id"="\d+"},
     *      defaults={"activeGroup" = null, "activeSubGroup" = null}
     * )
     * @Template()
     * @AclAncestor("oro_organization_update")
     *
     * @param Request $request
     * @param Website $entity
     * @param string|null $activeGroup
     * @param string|null $activeSubGroup
     * @return array
     */
    public function websiteConfigAction(
        Request $request,
        Website $entity,
        $activeGroup = null,
        $activeSubGroup = null
    ) {
        $provider = $this->get('orob2b_website_config.provider.form_provider');

        list($activeGroup, $activeSubGroup) = $provider->chooseActiveGroups($activeGroup, $activeSubGroup);

        $tree = $provider->getTree();
        $form = false;

        if ($activeSubGroup !== null) {
            $form = $provider->getForm($activeSubGroup);

            /** @var ConfigManager $manager */
            $manager = $this->get('oro_config.website');

            $prevScopeId = $manager->getScopeId();
            $manager->setScopeId($entity->getId());

            if ($this->get('oro_config.form.handler.config')
                ->setConfigManager($manager)
                ->process($form, $request)
            ) {
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.config.controller.config.saved.message')
                );

                // outdate content tags, it's only special case for generation that are not covered by NavigationBundle
                $taggableData = ['name' => 'website_configuration', 'params' => [$activeGroup, $activeSubGroup]];
                $sender       = $this->get('oro_navigation.content.topic_sender');

                $sender->send($sender->getGenerator()->generate($taggableData));
            }

            $manager->setScopeId($prevScopeId);
        }

        return array(
            'entity'         => $entity,
            'data'           => $tree,
            'form'           => $form ? $form->createView() : null,
            'activeGroup'    => $activeGroup,
            'activeSubGroup' => $activeSubGroup,
        );
    }
}
