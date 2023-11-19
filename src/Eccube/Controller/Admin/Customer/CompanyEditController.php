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

namespace Eccube\Controller\Admin\Customer;

use Eccube\Controller\AbstractController;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Entity\Company;
use Eccube\Form\Type\Admin\CompanyType;
use Eccube\Repository\CompanyRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CompanyEditController extends AbstractController
{
    /**
     * @var CompanyRepository
     */
    protected $companyRepository;

    public function __construct(
        CompanyRepository $companyRepository
    ) {
        $this->companyRepository = $companyRepository;
    }
    const MAX_LEN = 6;

   	/**
     * @Route("/%eccube_admin_route%/com/new", name="admin_company_new")
     * @Route("/%eccube_admin_route%/company/{id}/edit", requirements={"id" = "^[a-zA-Z0-9]+$"}, name="admin_company_edit")
     * @Template("@admin/Company/create.twig")
     */
    public function index(Request $request, $id = null)
    {
        // Check update
        if (is_numeric($id) && strlen($id) <= CompanyEditController::MAX_LEN) {
            $Company = $this->companyRepository
                ->find($id);

            if (is_null($Company)) {
                $Company = new Company();
            }
        // Create
        } else {
            $Company = new Company();
        }

        //Tạo from company
        $builder = $this->formFactory
            ->createBuilder(CompanyType::class, $Company);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Company' => $Company,
            ],
            $request
        );

        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_COMPANY_EDIT_INDEX_INITIALIZE, $event);

        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //Tìm kiếm công ty có ID giống với ID được nhập
            $company = $this->companyRepository->find($Company->getId());

            //Nếu ID công ty được nhập đã tồn tại
            if (($company && $Company->getId() !== $id) || ($company && is_null($id)))
            {
                // Thông báo lỗi ID đã được sử dụng.
                $this->addError('admin.common.id_error', 'admin');

                //Trả về màn hình đăng kí
                return [
                    'form' => $form->createView(),
                    'Company' => $Company,
                ];
                log_info('Create fail.', [$Company->getId()]);
            }

            //Create data
            log_info('Start register company', [$Company->getId()]);

            $this->entityManager->persist($Company);
            $this->entityManager->flush();

            log_info('Register company success. ', [$Company->getId()]);

            $event = new EventArgs(
                [
                    'form' => $form,
                    'Company' => $Company,
                ],
                $request
            );

            $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_COMPANY_EDIT_INDEX_COMPLETE, $event);
            
            //Thông báo đăng kí thành công
            $this->addSuccess('admin.common.save_complete', 'admin');

            //Trả về màn hình edit
            return $this->redirectToRoute('admin_company_edit', [
                'id' => $Company->getId(),
            ]);
        }

        return [
            'form' => $form->createView(),
            'Company' => $Company,
        ];
    }
}