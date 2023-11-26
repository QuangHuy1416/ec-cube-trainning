<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Controller\Mypage;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Repository\CustomerRepository;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\SearchCustomerBlockType;
use Eccube\Service\MailService;
use Eccube\Util\StringUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Eccube\Form\Type\Admin\SearchCustomerType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Util\FormUtil;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ThanksRegistrationController extends AbstractController
{
    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * ThanksRegistrationController constructor.
     *
     * @param MailService $mailService
     * @param CustomerRepository $customerRepository
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        MailService $mailService,
        CustomerRepository $customerRepository,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack
    ) {
        $this->mailService = $mailService;
        $this->customerRepository = $customerRepository;
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
    }

    /**
     * 退会画面.
     *
     * @Route("/mypage/thanks_registration", name="mypage_thanks_registration")
     * @Template("Mypage/gift.twig")
     */
    public function index(Request $request)
    {
        $session = $this->session;
        // $builder = $this->formFactory
        //     ->createNamedBuilder('', SearchCustomerBlockType::class)
        //     ->setMethod('GET');

        $builder = $this->formFactory->createBuilder(SearchCustomerType::class);
        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_INDEX_INITIALIZE, $event);

        $searchForm = $builder->getForm();

        // $this->eventDispatcher->dispatch(EccubeEvents::FRONT_BLOCK_SEARCH_PRODUCT_INDEX_INITIALIZE, $event);
        // $searchForm = $builder->getForm();

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
            if ($searchForm->isValid()) {
                $searchData = $searchForm->getData();
                $page_no = 1;

                $session->set('eccube.admin.customer.search', FormUtil::getViewData($searchForm));
                $session->set('eccube.admin.customer.search.page_no', $page_no);
            } else {
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'has_errors' => true,
                ];
            }
        } 
        $request = $this->requestStack->getMasterRequest();

        
        $searchForm->handleRequest($request);

        $qb = $this->customerRepository->findAll();

        $cus = $this->customerRepository->getQueryBuilderBySearchData($searchForm);
        return [
            'form' => $searchForm->createView(),
            'customers' => $qb,
            'cus' => $cus
        ];
    }
}
