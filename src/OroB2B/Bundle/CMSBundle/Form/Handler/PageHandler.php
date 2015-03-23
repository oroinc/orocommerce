<?php

namespace OroB2B\Bundle\CMSBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CMSBundle\Entity\Page;
use OroB2B\Bundle\RedirectBundle\Manager\SlugManager;

class PageHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ObjectManager */
    protected $manager;

    /** @var SlugManager */
    protected $slugManager;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     */
    public function __construct(FormInterface $form, Request $request, ObjectManager $manager, SlugManager $slugManager)
    {
        $this->form        = $form;
        $this->request     = $request;
        $this->manager     = $manager;
        $this->slugManager = $slugManager;
    }

    /**
     * @param Page $page
     * @return bool True on successful processing, false otherwise
     */
    public function process(Page $page)
    {
        $this->form->setData($page);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->slugManager->makeUrlUnique($page->getCurrentSlug());

                $this->manager->persist($page);
                $this->manager->flush();

                return true;
            }
        }

        return false;
    }
}
