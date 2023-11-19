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

namespace Eccube\Entity\Master;

use Doctrine\ORM\Mapping as ORM;

/**
 * SaleType
 *
 * @ORM\Table(name="mtb_sale_type")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Eccube\Repository\Master\SaleTypeRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class SaleType extends \Eccube\Entity\Master\AbstractMasterEntity
{
    /**
     * @var integer
     */
    const SALE_TYPE_NORMAL = 1;
}
