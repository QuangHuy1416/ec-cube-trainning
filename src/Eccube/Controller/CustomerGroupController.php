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

 namespace Eccube\Controller;

use Eccube\Common\EccubeConfig;
use Eccube\Repository\CompanyRepository;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\CustomerGroupRepository;
use Eccube\Controller\AbstractController;
use Eccube\Entity\CustomerGroup;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CustomerGroupController extends AbstractController
{
    /**
     * @var CompanyRepository
     */
    protected $companyRepository;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var CustomerGroupRepository
     */
    protected $customerGroupRepository;

    /**
     * @var EccubeConfig
     */
    protected $shop_api_business_id;

    public function __construct(
        CompanyRepository $companyRepository,
        CustomerRepository $customerRepository,
        CustomerGroupRepository $customerGroupRepository,
        EccubeConfig $eccubeConfig
    )
    {
        $this->companyRepository = $companyRepository;
        $this->customerRepository = $customerRepository;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->shop_api_business_id = $eccubeConfig['shop_api_business_id'];
    }

    /**
     * Method này dùng để thực hiện đăng ký company cho customer
     * 
     * @Route("/addCustomerGroup",  name="shop_api_add_customer_group", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function addCustomerGroup(Request $request)
    {
        $businessId = $request->get("businessId");
        // Kiểm tra businessId có hợp lệ hay không
        if( $this->shop_api_business_id !== $businessId ){
            // Ghi log lỗi
            log_info('API KEY NOT AUTH', [$businessId]);
            return $this->json(['result' => 1, 'resultCode'=> 'businessId NOT FOUND']);
        }

        // Get accountId
        $accountId = trim($request->get("accountId"));
        
        // Get companyId
        $companyId = trim($request->get("companyId"));

        // Tìm kiếm company
        $company = $this->companyRepository->find($companyId);

        // Kiểm tra tồn tại company
        if(!$company){
            // Ghi log lỗi
            log_info('Register customer group fail.', [$companyId]);
            return $this->json(['result' => 1, 'resultCode'=> 'Company not exist.']);
        }

        // Tìm kiếm customer
        $customer = $this->customerRepository->find($accountId);

        // Kiểm tra tồn tại của customer
        if(!$customer){
            // Ghi log lỗi
            log_info('Register customer group fail.', [$accountId]);
            return $this->json(['result' => 1, 'resultCode'=> 'Customer not exist.']);
        }

        // Tìm kiếm customer group theo accountId
        $customerGroup = $this->customerGroupRepository->find($accountId);

        // Kiểm tra tồn tại của customer
        if($customerGroup){
            // Kiểm tra companyId của customerGroup có trùng với companyId truyền vào hay không
            if($customerGroup->getCompanyId() === $companyId){
                // Ghi log lỗi
                log_info('Register customer group fail.', [$customerGroup->getAccountId()]);
                return $this->json(['result' => 1, 'resultCode'=> 'Customer Group có account_id = ' . $accountId . ' đã tồn tại.']);
            } else {
                try{
                    // Update customerGroup
                    $customerGroup->setCompanyId($companyId);

                    log_info('Start update customer group.', [$customerGroup->getAccountId()]);
    
                    $this->entityManager->persist($customerGroup);
                    $this->entityManager->flush();
    
                    log_info('Update customer group success.', [$customerGroup->getAccountId()]);
    
                    return $this->json(['result' => 0, 'resultCode'=> 'Update customer group success.']);
                }
                catch(Exception $e){
                    //Ghi log lỗi
                    log_info('Update customer group fail.' . $e, [$customerGroup->getAccountId()]);
                    return $this->json(['result' => 1, 'resultCode'=> 'Update thất bại! Customer Group có company_id = ' . $companyId .' đã tồn tại.']);
                }
            }
        }

        // Tìm kiếm customer group theo companyId
        $customerGroup = $this->customerGroupRepository->findBy(['company_id'=> $companyId]);

        if($customerGroup){
            // Ghi log lỗi
            log_info('Register customer group fail.', [$companyId]);
            return $this->json(['result' => 1, 'resultCode'=> 'Customer Group có company_id = ' . $companyId .' đã tồn tại.']);
        }

        //Create customerGroup
        $customerGroup = new CustomerGroup();

        $customerGroup->setAccountId($accountId);
        $customerGroup->setCompanyId($companyId);

        log_info('Start register customer group.', [$customerGroup->getAccountId()]);

        $this->entityManager->persist($customerGroup);
        $this->entityManager->flush();

        log_info('Register customer group success.', [$customerGroup->getAccountId()]);

        return $this->json(['result' => 0, 'resultCode'=> 'Register customer group success.']);
    }
}