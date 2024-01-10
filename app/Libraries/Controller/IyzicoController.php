<?php

namespace App\Controller;

use Helpers\DataBase\DB;
use App\Libraries\Iyzico;

class IyzicoController
{
    public static function payload()
    {
        $payment = [
            'orderId' => 'IS123e21asde3',
            'basketTotalPrice' => 9000,
            'userId' => 1,
            'userName' => 'Yusuf',
            'userSurname' => 'Hangün',
            'userPhone' => '05355923823',
            'userMail' => 'yusuf_hangun@outlook.com',
            'userCreatedAt' => '2024-01-09 15:04:32',
            'userCountry' => 'Turkiye',
            'userCity' => 'İstanbul',
            'addressDetail' => 'Adress detay',
        ];
        $basket = DB::table('basket')->where('user_id', $_SESSION['ISOFTUSER']->user_id)->get();//Gerçek verilerinizle değiştirin
        $cargo = DB::table('cargo_price')->where('lang', 'tr')->limit(1)->first();
        $form = Iyzico::options()->CreatePaymentRequest($payment, 'tr')
            ->buyer($payment)
            ->shippingAddress($payment)
            ->billingAddress($payment)
            ->basketItems($basket, $cargo)
            ->payment();
        echo $form;
    }
}
