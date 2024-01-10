<?php

namespace App\Libraries;

use Helpers\DataBase\DB;//Kendi DB Classınız ile değiştirin
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Currency;
use Iyzipay\Model\Locale;
use Iyzipay\Model\PaymentGroup;
use Iyzipay\Options;

class Iyzico
{
    protected static $request;
    protected static $options;
    protected static $buyer;
    protected static $shippingAddress;
    protected static $billingAddress;
    protected static $payment;

    public static function options() // Iyzico key ayarları
    {
        self::$options = new Options;
        self::$options->setApiKey("sandbox-lt2WB9gUJVBA7nvWBkJOQSyJcBx8zFHT");
        self::$options->setSecretKey("sandbox-TQyrt5Tu4MiUpBjYduHiFRcBhywbEw3D");
        self::$options->setBaseUrl("https://sandbox-api.iyzipay.com");
        return new self;
    }

    public static function CreatePaymentRequest(array $payment, $lang)//Sipariş Numarası ve Sipariş toplam fiyatı
    {
        $paymentType = ($lang === 'tr') ? Currency::TL : Currency::USD;
        $localeType = ($lang === 'tr') ? Locale::TR : Locale::EN;
        self::$request = new \Iyzipay\Request\CreateCheckoutFormInitializeRequest();
        self::$request->setLocale($localeType);
        self::$request->setConversationId($payment['orderId']);
        self::$request->setPrice($payment['basketTotalPrice']);
        self::$request->setPaidPrice($payment['basketTotalPrice']);
        self::$request->setCurrency($paymentType);
        self::$request->setBasketId($payment['orderId']);
        self::$request->setPaymentGroup(PaymentGroup::PRODUCT);
        self::$request->setCallbackUrl('https://localhost/ecommerce/helper/iyzico');//Kendi backurl ' iniz ile değiştirin
        self::$request->setEnabledInstallments(array(2, 3, 6, 9));
        return new self;
    }

    public static function buyer(array $payment)//Alıcı Bilgileri
    {
        self::$buyer = new \Iyzipay\Model\Buyer();
        self::$buyer->setId($payment['userId']);
        self::$buyer->setName($payment['userName']);
        self::$buyer->setSurname($payment['userSurname']);
        self::$buyer->setGsmNumber($payment['userPhone']);
        self::$buyer->setEmail($payment['userMail']);
        self::$buyer->setIdentityNumber("11111111111");
        self::$buyer->setRegistrationDate($payment['userCreatedAt']);
        self::$buyer->setRegistrationAddress($payment['addressDetail']);
        self::$buyer->setIp($_SERVER['REMOTE_ADDR']);
        self::$buyer->setCity($payment['userCity']);
        self::$buyer->setCountry($payment['userCountry']);
        self::$request->setBuyer(self::$buyer);
        return new self;
    }

    public static function shippingAddress(array $payment)//Teslim Adresi
    {
        self::$shippingAddress = new \Iyzipay\Model\Address();
        self::$shippingAddress->setContactName($payment['userName']);
        self::$shippingAddress->setCity($payment['userCity']);
        self::$shippingAddress->setCountry($payment['userCountry']);
        self::$shippingAddress->setAddress($payment['addressDetail']);
        self::$request->setShippingAddress(self::$shippingAddress);
        return new self;
    }

    public static function billingAddress(array $payment)//Fatura Adresi
    {
        self::$billingAddress = new \Iyzipay\Model\Address();
        self::$billingAddress->setContactName($payment['userName']);
        self::$billingAddress->setCity($payment['userCity']);
        self::$billingAddress->setCountry($payment['userCountry']);
        self::$billingAddress->setAddress($payment['addressDetail']);
        self::$request->setBillingAddress(self::$billingAddress);
        return new self;
    }

    public static function basketItems($basket, $cargo) // Sepetten gelen ürünleri yazdırma 
    {
        $set_cargo_price = $cargo->set_cargo_price;
        $basketTotal = 0;
        foreach ($basket as $baskets) {
            $product = DB::table('products')->where(['product_id' => $baskets->product_id])->first();
            $cat = DB::table('category')->where(['cat_id' => 1])->first();
            $item = new BasketItem();
            $item->setId($baskets->basket_id);
            $item->setName($product->product_title);
            $item->setCategory1($cat->cat_title);
            $item->setItemType(BasketItemType::PHYSICAL);
            $item->setPrice(($product->product_price * $baskets->product_piece));
            $basketTotal += $product->product_price * $baskets->product_piece;
            if (($cargo->set_cargo) > ($basketTotal) && empty($basketItems)) { // sepet toplamı kargo eşiğinden küçükse kargo fiyatını ekletiyoruz
                $shippingItem = new BasketItem();
                $shippingItem->setId($baskets->basket_id);
                $shippingItem->setName($product->product_title);
                $shippingItem->setCategory1($cat->cat_title);
                $shippingItem->setItemType(BasketItemType::VIRTUAL);
                $shippingItem->setPrice($set_cargo_price);
                $basketItems[] = $shippingItem;
            }
            $basketItems[] = $item;
        }
        self::$request->setBasketItems($basketItems);
        return new self;
    }

    public static function payment()//Ödeme formunu oluşturma
    {
        self::$payment = \Iyzipay\Model\CheckoutFormInitialize::create(self::$request, self::$options);
        self::getStatus();
        return self::$payment->getCheckoutFormContent();
    }

    public static function getStatus()
    {
        if (self::$payment->getStatus() !== "success") {
            echo self::$payment->getErrorMessage();
        }
    }
}
