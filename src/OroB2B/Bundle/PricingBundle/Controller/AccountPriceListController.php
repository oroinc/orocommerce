<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use Symfony\Component\Form\Form;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use OroB2B\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountPriceListController extends Controller
{
    /**
     * @Route(
     *      "/edit/{accountId}/website/{id}",
     *      name="orob2b_account_pricelist_website",
     *      requirements={"accountId"="\d+", "id"="\d+"}
     * )
     * @ParamConverter("account", options={"id" = "accountId"})
     * @Template("OroB2BAccountBundle:AccountVisibility/widget:website.html.twig")
     * @AclAncestor("orob2b_account_update")
     *
     * @param Account $account
     * @param Website $website
     * @return array
     */
    public function websiteWidgetAction(Account $account, Website $website)
    {
        /** @var Form $form */
        $form = $this->createWebsiteScopedDataForm($account, [$website]);

        return [
            'form' => $form->createView()[$website->getId()],
            'entity' => $account,
            'website' => $website,
        ];
    }

    /**
     * @param Account $account
     * @param array $preloaded_websites
     * @return Form
     */
    protected function createWebsiteScopedDataForm(Account $account, array $preloaded_websites)
    {
        return $this->createForm(
            WebsiteScopedDataType::NAME,
            $account,
            [
                'ownership_disabled' => true,
                'preloaded_websites' => $preloaded_websites,
                'type' => PriceListCollectionType::NAME,
                'options' => [
                    'data' => []
                ],
                'data' => [],
            ]
        );
    }
}
