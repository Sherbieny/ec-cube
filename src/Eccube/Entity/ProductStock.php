<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Entity;

use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Eccube\Entity\ProductStock')) {
    /**
     * ProductStock
     *
     * @ORM\Table(name="dtb_product_stock")
     *
     * @ORM\InheritanceType("SINGLE_TABLE")
     *
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     *
     * @ORM\HasLifecycleCallbacks()
     *
     * @ORM\Entity(repositoryClass="Eccube\Repository\ProductStockRepository")
     */
    class ProductStock extends AbstractEntity
    {
        public const IN_STOCK = 1;
        public const OUT_OF_STOCK = 2;

        /**
         * @var int
         */
        private $product_class_id;

        /**
         * Set product_class_id
         *
         * @param int $productClassId
         *
         * @return ProductStock
         */
        public function setProductClassId($productClassId)
        {
            $this->product_class_id = $productClassId;

            return $this;
        }

        /**
         * Get product_class_id
         *
         * @return int
         */
        public function getProductClassId()
        {
            return $this->product_class_id;
        }

        /**
         * @var int
         *
         * @ORM\Column(name="id", type="integer", options={"unsigned":true})
         *
         * @ORM\Id
         *
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;

        /**
         * @var string|null
         *
         * @ORM\Column(name="stock", type="decimal", precision=10, scale=0, nullable=true)
         */
        private $stock;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="create_date", type="datetimetz")
         */
        private $create_date;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="update_date", type="datetimetz")
         */
        private $update_date;

        /**
         * @var ProductClass
         *
         * @ORM\OneToOne(targetEntity="Eccube\Entity\ProductClass", inversedBy="ProductStock")
         *
         * @ORM\JoinColumns({
         *
         *   @ORM\JoinColumn(name="product_class_id", referencedColumnName="id")
         * })
         */
        private $ProductClass;

        /**
         * @var Member
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member")
         *
         * @ORM\JoinColumns({
         *
         *   @ORM\JoinColumn(name="creator_id", referencedColumnName="id")
         * })
         */
        private $Creator;

        /**
         * Get id.
         *
         * @return int
         */
        public function getId()
        {
            return $this->id;
        }

        /**
         * Set stock.
         *
         * @param string|null $stock
         *
         * @return ProductStock
         */
        public function setStock($stock = null)
        {
            $this->stock = $stock;

            return $this;
        }

        /**
         * Get stock.
         *
         * @return string|null
         */
        public function getStock()
        {
            return $this->stock;
        }

        /**
         * Set createDate.
         *
         * @param \DateTime $createDate
         *
         * @return ProductStock
         */
        public function setCreateDate($createDate)
        {
            $this->create_date = $createDate;

            return $this;
        }

        /**
         * Get createDate.
         *
         * @return \DateTime
         */
        public function getCreateDate()
        {
            return $this->create_date;
        }

        /**
         * Set updateDate.
         *
         * @param \DateTime $updateDate
         *
         * @return ProductStock
         */
        public function setUpdateDate($updateDate)
        {
            $this->update_date = $updateDate;

            return $this;
        }

        /**
         * Get updateDate.
         *
         * @return \DateTime
         */
        public function getUpdateDate()
        {
            return $this->update_date;
        }

        /**
         * Set productClass.
         *
         * @param ProductClass|null $productClass
         *
         * @return ProductStock
         */
        public function setProductClass(?ProductClass $productClass = null)
        {
            $this->ProductClass = $productClass;

            return $this;
        }

        /**
         * Get productClass.
         *
         * @return ProductClass|null
         */
        public function getProductClass()
        {
            return $this->ProductClass;
        }

        /**
         * Set creator.
         *
         * @param Member|null $creator
         *
         * @return ProductStock
         */
        public function setCreator(?Member $creator = null)
        {
            $this->Creator = $creator;

            return $this;
        }

        /**
         * Get creator.
         *
         * @return Member|null
         */
        public function getCreator()
        {
            return $this->Creator;
        }
    }
}
