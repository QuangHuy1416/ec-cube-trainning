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

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\QueryBuilder;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Admin\SearchCompanyType;
use Eccube\Repository\CompanyRepository;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class CompanyController extends AbstractController
{
    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var CompanyRepository
     */
    protected $companyRepository;

    public function __construct(
        PageMaxRepository $pageMaxRepository,
        CompanyRepository $companyRepository
    ) {
        $this->pageMaxRepository = $pageMaxRepository;
        $this->companyRepository = $companyRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/company", name="admin_company")
     * @Route("/%eccube_admin_route%/company/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_company_page")
     * @Template("@admin/Company/index.twig")
     */
    public function index(Request $request, $page_no = null, Paginator $paginator)
    {
        $session = $this->session;
        $builder = $this->formFactory->createBuilder(SearchCompanyType::class);

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_COMPANY_INDEX_INITIALIZE, $event);

        $searchForm = $builder->getForm();

        $pageMaxis = $this->pageMaxRepository->findAll();
        $pageCount = $session->get('eccube.admin.company.search.page_count', $this->eccubeConfig['eccube_default_page_count']);
        $pageCountParam = $request->get('page_count');
        if ($pageCountParam && is_numeric($pageCountParam)) {
            foreach ($pageMaxis as $pageMax) {
                if ($pageCountParam == $pageMax->getName()) {
                    $pageCount = $pageMax->getName();
                    $session->set('eccube.admin.company.search.page_count', $pageCount);
                    break;
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
            if ($searchForm->isValid()) {
                $searchData = $searchForm->getData();
                $page_no = 1;

                $session->set('eccube.admin.company.search', FormUtil::getViewData($searchForm));
                $session->set('eccube.admin.company.search.page_no', $page_no);
            } else {
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $pageCount,
                    'has_errors' => true,
                ];
            }
        } else {
            if (null !== $page_no || $request->get('resume')) {
                if ($page_no) {
                    $session->set('eccube.admin.company.search.page_no', (int) $page_no);
                } else {
                    $page_no = $session->get('eccube.admin.company.search.page_no', 1);
                }
                $viewData = $session->get('eccube.admin.company.search', []);
            } else {
                $page_no = 1;
                $viewData = FormUtil::getViewData($searchForm);
                $session->set('eccube.admin.company.search', $viewData);
                $session->set('eccube.admin.company.search.page_no', $page_no);
            }
            $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
        }

        /** @var QueryBuilder $qb */
        $qb = $this->companyRepository->getList($searchData);

        $event = new EventArgs(
            [
                'form' => $searchForm,
                'qb' => $qb,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_COMPANY_INDEX_SEARCH, $event);

        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $pageCount
        );

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $pageCount,
            'has_errors' => false,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/company/{id}/delete", requirements={"id" = "\d+"}, name="admin_company_delete", methods={"DELETE"})
     */
    public function delete(Request $request, $id, TranslatorInterface $translator)
    {
        $this->isTokenValid();

        log_info('Bắt đầu xóa công ty', [$id]);

        $page_no = intval($this->session->get('eccube.admin.company.search.page_no'));
        $page_no = $page_no ? $page_no : Constant::ENABLED;

        $company = $this->companyRepository
            ->find($id);

        if (!$company) {
            $this->deleteMessage();

            return $this->redirect($this->generateUrl('admin_company_page',
                    ['page_no' => $page_no]).'?resume='.Constant::ENABLED);
        }

        try {
            $this->entityManager->remove($company);
            $this->entityManager->flush($company);
            $this->addSuccess('admin.company.delete.complete', 'admin');
        } catch (ForeignKeyConstraintViolationException $e) {
            log_error('Xóa không thành công', [$e], 'admin');
            $message = trans('admin.common.delete_error_foreign_key', ['%name%' => $company->getName()]);
            $this->addError($message, 'admin');
        }

        log_info('Công ty đã bị xóa', [$id]);

        $event = new EventArgs(
            [
                'company' => $company,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_COMPANY_DELETE_COMPLETE, $event);

        return $this->redirect($this->generateUrl('admin_company_page',
                ['page_no' => $page_no]).'?resume='.Constant::ENABLED);
    }
}
