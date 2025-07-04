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

namespace Eccube\Service;

use Detection\MobileDetect;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Cart;
use Eccube\Entity\CartItem;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\EventListener\SecurityListener;
use Eccube\Repository\DeliveryRepository;
use Eccube\Repository\Master\DeviceTypeRepository;
use Eccube\Repository\Master\OrderItemTypeRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\Master\PrefRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Session\Session;
use Eccube\Util\StringUtil;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class OrderHelper
{
    /**
     * @var string 非会員情報を保持するセッションのキー
     */
    public const SESSION_NON_MEMBER = 'eccube.front.shopping.nonmember';

    /**
     * @var string 非会員の住所情報を保持するセッションのキー
     */
    public const SESSION_NON_MEMBER_ADDRESSES = 'eccube.front.shopping.nonmember.customeraddress';

    /**
     * @var string 受注IDを保持するセッションのキー
     */
    public const SESSION_ORDER_ID = 'eccube.front.shopping.order.id';

    /**
     * @var string カートが分割されているかどうかのフラグ. 購入フローからのログイン時にカートが分割された場合にtrueがセットされる.
     *
     * @see SecurityListener
     */
    public const SESSION_CART_DIVIDE_FLAG = 'eccube.front.cart.divide';

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var PrefRepository
     */
    protected $prefRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderItemTypeRepository
     */
    protected $orderItemTypeRepository;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var DeliveryRepository
     */
    protected $deliveryRepository;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var DeviceTypeRepository
     */
    protected $deviceTypeRepository;

    /**
     * @var MobileDetector
     */
    protected $mobileDetector;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    public function __construct(
        EntityManagerInterface $entityManager,
        OrderRepository $orderRepository,
        OrderItemTypeRepository $orderItemTypeRepository,
        OrderStatusRepository $orderStatusRepository,
        DeliveryRepository $deliveryRepository,
        PaymentRepository $paymentRepository,
        DeviceTypeRepository $deviceTypeRepository,
        PrefRepository $prefRepository,
        MobileDetect $mobileDetector,
        Session $session,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->orderItemTypeRepository = $orderItemTypeRepository;
        $this->deliveryRepository = $deliveryRepository;
        $this->paymentRepository = $paymentRepository;
        $this->deviceTypeRepository = $deviceTypeRepository;
        $this->entityManager = $entityManager;
        $this->prefRepository = $prefRepository;
        $this->mobileDetector = $mobileDetector;
        $this->session = $session;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * 購入処理中の受注を生成する.
     *
     * @param Customer $Customer
     * @param $CartItems
     *
     * @return Order
     */
    public function createPurchaseProcessingOrder(Cart $Cart, Customer $Customer)
    {
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PROCESSING);
        $Order = new Order($OrderStatus);

        $preOrderId = $this->createPreOrderId();
        $Order->setPreOrderId($preOrderId);

        // 顧客情報の設定
        $this->setCustomer($Order, $Customer);

        $DeviceType = $this->deviceTypeRepository->find($this->mobileDetector->isMobile() ? DeviceType::DEVICE_TYPE_MB : DeviceType::DEVICE_TYPE_PC);
        $Order->setDeviceType($DeviceType);

        // 明細情報の設定
        $OrderItems = $this->createOrderItemsFromCartItems($Cart->getCartItems());
        $OrderItemsGroupBySaleType = array_reduce($OrderItems, function ($result, $item) {
            /* @var OrderItem $item */
            $saleTypeId = $item->getProductClass()->getSaleType()->getId();
            $result[$saleTypeId][] = $item;

            return $result;
        }, []);

        foreach ($OrderItemsGroupBySaleType as $OrderItems) {
            $Shipping = $this->createShippingFromCustomer($Customer);
            $Shipping->setOrder($Order);
            $this->addOrderItems($Order, $Shipping, $OrderItems);
            $this->setDefaultDelivery($Shipping);
            $this->entityManager->persist($Shipping);
            $Order->addShipping($Shipping);
        }

        $this->setDefaultPayment($Order);

        $this->entityManager->persist($Order);

        return $Order;
    }

    /**
     * @param Cart $Cart
     *
     * @return bool
     */
    public function verifyCart(Cart $Cart)
    {
        if (count($Cart->getCartItems()) > 0) {
            $divide = $this->session->get(self::SESSION_CART_DIVIDE_FLAG);
            if ($divide) {
                log_info('ログイン時に販売種別が異なる商品がカートと結合されました。');

                return false;
            }

            return true;
        }

        log_info('カートに商品が入っていません。');

        return false;
    }

    /**
     * 注文手続き画面でログインが必要かどうかの判定
     *
     * @return bool
     */
    public function isLoginRequired()
    {
        // フォームログイン済はログイン不要
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return false;
        }

        // Remember Meログイン済の場合はフォームからのログインが必要
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return true;
        }

        // 未ログインだがお客様情報を入力している場合はログイン不要
        if (!$this->getUser() && $this->getNonMember()) {
            return false;
        }

        return true;
    }

    /**
     * 購入処理中の受注を取得する.
     *
     * @param string|null $preOrderId
     *
     * @return Order|null
     */
    public function getPurchaseProcessingOrder($preOrderId = null)
    {
        if (null === $preOrderId) {
            return null;
        }

        return $this->orderRepository->findOneBy([
            'pre_order_id' => $preOrderId,
            'OrderStatus' => OrderStatus::PROCESSING,
        ]);
    }

    /**
     * セッションに保持されている非会員情報を取得する.
     * 非会員購入時に入力されたお客様情報を返す.
     *
     * @param string $session_key
     *
     * @return Customer|null
     */
    public function getNonMember($session_key = self::SESSION_NON_MEMBER)
    {
        $data = $this->session->get($session_key);
        if (empty($data)) {
            return null;
        }
        $Customer = new Customer();
        $Customer
            ->setName01($data['name01'])
            ->setName02($data['name02'])
            ->setKana01($data['kana01'])
            ->setKana02($data['kana02'])
            ->setCompanyName($data['company_name'])
            ->setEmail($data['email'])
            ->setPhonenumber($data['phone_number'])
            ->setPostalcode($data['postal_code'])
            ->setAddr01($data['addr01'])
            ->setAddr02($data['addr02']);

        if (!empty($data['pref'])) {
            $Pref = $this->prefRepository->find($data['pref']);
            $Customer->setPref($Pref);
        }

        return $Customer;
    }

    /**
     * @param Cart $Cart
     * @param Customer $Customer
     *
     * @return Order|null
     */
    public function initializeOrder(Cart $Cart, Customer $Customer)
    {
        // 購入処理中の受注情報を取得
        if ($Order = $this->getPurchaseProcessingOrder($Cart->getPreOrderId())) {
            return $Order;
        }

        // 受注情報を作成
        $Order = $this->createPurchaseProcessingOrder($Cart, $Customer);
        $Cart->setPreOrderId($Order->getPreOrderId());

        return $Order;
    }

    public function removeSession()
    {
        $this->session->remove(self::SESSION_ORDER_ID);
        $this->session->remove(self::SESSION_NON_MEMBER);
        $this->session->remove(self::SESSION_NON_MEMBER_ADDRESSES);
    }

    /**
     * 会員情報の更新日時が受注の作成日時よりも新しければ, 受注の注文者情報を更新する.
     *
     * @param Order $Order
     * @param Customer $Customer
     */
    public function updateCustomerInfo(Order $Order, Customer $Customer)
    {
        if ($Order->getCreateDate() < $Customer->getUpdateDate()) {
            $this->setCustomer($Order, $Customer);
        }
    }

    public function createPreOrderId()
    {
        // ランダムなpre_order_idを作成
        do {
            $preOrderId = sha1(StringUtil::random(32));

            $Order = $this->orderRepository->findOneBy(
                [
                    'pre_order_id' => $preOrderId,
                ]
            );
        } while ($Order);

        return $preOrderId;
    }

    protected function setCustomer(Order $Order, Customer $Customer)
    {
        if ($Customer->getId()) {
            $Order->setCustomer($Customer);
        }

        $Order->copyProperties(
            $Customer,
            [
                'id',
                'create_date',
                'update_date',
                'del_flg',
            ]
        );
    }

    /**
     * @param Collection|ArrayCollection|CartItem[] $CartItems
     *
     * @return OrderItem[]
     */
    protected function createOrderItemsFromCartItems($CartItems)
    {
        $ProductItemType = $this->orderItemTypeRepository->find(OrderItemType::PRODUCT);

        return array_map(function ($item) use ($ProductItemType) {
            /** @var CartItem $item */
            /** @var \Eccube\Entity\ProductClass $ProductClass */
            $ProductClass = $item->getProductClass();
            /** @var \Eccube\Entity\Product $Product */
            $Product = $ProductClass->getProduct();

            $OrderItem = new OrderItem();
            $OrderItem
                ->setProduct($Product)
                ->setProductClass($ProductClass)
                ->setProductName($Product->getName())
                ->setProductCode($ProductClass->getCode())
                ->setPrice($ProductClass->getPrice02())
                ->setQuantity($item->getQuantity())
                ->setOrderItemType($ProductItemType);

            $ClassCategory1 = $ProductClass->getClassCategory1();
            if (!is_null($ClassCategory1)) {
                $OrderItem->setClasscategoryName1($ClassCategory1->getName());
                $OrderItem->setClassName1($ClassCategory1->getClassName()->getName());
            }
            $ClassCategory2 = $ProductClass->getClassCategory2();
            if (!is_null($ClassCategory2)) {
                $OrderItem->setClasscategoryName2($ClassCategory2->getName());
                $OrderItem->setClassName2($ClassCategory2->getClassName()->getName());
            }

            return $OrderItem;
        }, $CartItems instanceof Collection ? $CartItems->toArray() : $CartItems);
    }

    /**
     * @param Customer $Customer
     *
     * @return Shipping
     */
    protected function createShippingFromCustomer(Customer $Customer)
    {
        $Shipping = new Shipping();
        $Shipping
            ->setName01($Customer->getName01())
            ->setName02($Customer->getName02())
            ->setKana01($Customer->getKana01())
            ->setKana02($Customer->getKana02())
            ->setCompanyName($Customer->getCompanyName())
            ->setPhoneNumber($Customer->getPhoneNumber())
            ->setPostalCode($Customer->getPostalCode())
            ->setPref($Customer->getPref())
            ->setAddr01($Customer->getAddr01())
            ->setAddr02($Customer->getAddr02());

        return $Shipping;
    }

    /**
     * @param Shipping $Shipping
     */
    protected function setDefaultDelivery(Shipping $Shipping)
    {
        // 配送商品に含まれる販売種別を抽出.
        $OrderItems = $Shipping->getOrderItems();
        $SaleTypes = [];
        /** @var OrderItem $OrderItem */
        foreach ($OrderItems as $OrderItem) {
            $ProductClass = $OrderItem->getProductClass();
            $SaleType = $ProductClass->getSaleType();
            $SaleTypes[$SaleType->getId()] = $SaleType;
        }

        // 販売種別に紐づく配送業者を取得.
        $Deliveries = $this->deliveryRepository->getDeliveries($SaleTypes);

        // 初期の配送業者を設定
        $Delivery = current($Deliveries);
        $Shipping->setDelivery($Delivery);
        $Shipping->setShippingDeliveryName($Delivery->getName());
    }

    /**
     * @param Order $Order
     */
    protected function setDefaultPayment(Order $Order)
    {
        $OrderItems = $Order->getOrderItems();

        // 受注明細に含まれる販売種別を抽出.
        $SaleTypes = [];
        /** @var OrderItem $OrderItem */
        foreach ($OrderItems as $OrderItem) {
            $ProductClass = $OrderItem->getProductClass();
            if (is_null($ProductClass)) {
                // 商品明細のみ対象とする. 送料明細等はスキップする.
                continue;
            }
            $SaleType = $ProductClass->getSaleType();
            $SaleTypes[$SaleType->getId()] = $SaleType;
        }

        // 販売種別に紐づく配送業者を抽出
        $Deliveries = $this->deliveryRepository->getDeliveries($SaleTypes);

        // 利用可能な支払い方法を抽出.
        // ここでは支払総額が決まっていないため、利用条件に合致しないものも選択対象になる場合がある
        $Payments = $this->paymentRepository->findAllowedPayments($Deliveries, true);

        // 初期の支払い方法を設定.
        $Payment = current($Payments);
        if ($Payment) {
            $Order->setPayment($Payment);
            $Order->setPaymentMethod($Payment->getMethod());
        }
    }

    /**
     * @param Order $Order
     * @param Shipping $Shipping
     * @param array $OrderItems
     */
    protected function addOrderItems(Order $Order, Shipping $Shipping, array $OrderItems)
    {
        foreach ($OrderItems as $OrderItem) {
            $Shipping->addOrderItem($OrderItem);
            $Order->addOrderItem($OrderItem);
            $OrderItem->setOrder($Order);
            $OrderItem->setShipping($Shipping);
        }
    }

    /**
     * @see Symfony\Bundle\FrameworkBundle\Controller\AbstractController
     */
    private function isGranted($attribute, $subject = null): bool
    {
        return $this->authorizationChecker->isGranted($attribute, $subject);
    }

    /**
     * @see Symfony\Bundle\FrameworkBundle\Controller\AbstractController
     */
    private function getUser(): ?UserInterface
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        if (!\is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }

    /**
     * 税表示区分を取得する.
     *
     * - 商品: 税抜
     * - 送料: 税込
     * - 値引き: 税抜
     * - 手数料: 税込
     * - ポイント値引き: 税込
     *
     * @param $OrderItemType
     *
     * @return TaxDisplayType
     */
    public function getTaxDisplayType($OrderItemType)
    {
        if ($OrderItemType instanceof OrderItemType) {
            $OrderItemType = $OrderItemType->getId();
        }

        switch ($OrderItemType) {
            case OrderItemType::PRODUCT:
                return $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::EXCLUDED);
            case OrderItemType::DELIVERY_FEE:
                return $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::INCLUDED);
            case OrderItemType::DISCOUNT:
                return $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::EXCLUDED);
            case OrderItemType::CHARGE:
                return $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::INCLUDED);
            case OrderItemType::POINT:
                return $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::INCLUDED);
            default:
                return $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::EXCLUDED);
        }
    }
}
