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

if (!class_exists('\Eccube\Entity\ProductTag')) {
    /**
     * ProductTag
     *
     * @ORM\Table(name="dtb_product_tag")
     *
     * @ORM\InheritanceType("SINGLE_TABLE")
     *
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     *
     * @ORM\HasLifecycleCallbacks()
     *
     * @ORM\Entity(repositoryClass="Eccube\Repository\ProductTagRepository")
     */
    class ProductTag extends AbstractEntity
    {
        /**
         * Get tag_id
         * use csv export
         *
         * @return int
         */
        public function getTagId()
        {
            if (empty($this->Tag)) {
                return null;
            }

            return $this->Tag->getId();
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
         * @var \DateTime
         *
         * @ORM\Column(name="create_date", type="datetimetz")
         */
        private $create_date;

        /**
         * @var Product
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product", inversedBy="ProductTag")
         *
         * @ORM\JoinColumns({
         *
         *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
         * })
         */
        private $Product;

        /**
         * @var Tag
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Tag", inversedBy="ProductTag")
         *
         * @ORM\JoinColumns({
         *
         *   @ORM\JoinColumn(name="tag_id", referencedColumnName="id")
         * })
         */
        private $Tag;

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
         * Set createDate.
         *
         * @param \DateTime $createDate
         *
         * @return ProductTag
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
         * Set product.
         *
         * @param Product|null $product
         *
         * @return ProductTag
         */
        public function setProduct(?Product $product = null)
        {
            $this->Product = $product;

            return $this;
        }

        /**
         * Get product.
         *
         * @return Product|null
         */
        public function getProduct()
        {
            return $this->Product;
        }

        /**
         * Set tag.
         *
         * @param Tag|null $tag
         *
         * @return ProductTag
         */
        public function setTag(?Tag $tag = null)
        {
            $this->Tag = $tag;

            return $this;
        }

        /**
         * Get tag.
         *
         * @return Tag|null
         */
        public function getTag()
        {
            return $this->Tag;
        }

        /**
         * Set creator.
         *
         * @param Member|null $creator
         *
         * @return ProductTag
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
