<?php namespace App\Models;

use Eloquent;
use Omnipay;

class Gateway extends Eloquent
{
    public $timestamps = true;

    public function getLogoUrl()
    {
        return '/images/gateways/logo_'.$this->provider.'.png';
    }

    public function getHelp()
    {
        $link = '';

        if ($this->id == GATEWAY_AUTHORIZE_NET || $this->id == GATEWAY_AUTHORIZE_NET_SIM) {
            $link = 'http://reseller.authorize.net/application/?id=5560364';
        } elseif ($this->id == GATEWAY_PAYPAL_EXPRESS) {
            $link = 'https://www.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run';
        } elseif ($this->id == GATEWAY_TWO_CHECKOUT) {
            $link = 'https://www.2checkout.com/referral?r=2c37ac2298';
        } elseif ($this->id == GATEWAY_BITPAY) {
            $link = 'https://bitpay.com/dashboard/signup';
        }

        $key = 'texts.gateway_help_'.$this->id;
        $str = trans($key, ['link' => "<a href='$link' target='_blank'>Click here</a>"]);

        return $key != $str ? $str : '';
    }

    public function getFields()
    {
        return Omnipay::create($this->provider)->getDefaultParameters();
    }

    public static function getPaymentType($gatewayId) {
        if ($gatewayId == GATEWAY_PAYPAL_EXPRESS) {
            return PAYMENT_TYPE_PAYPAL;
        } else if ($gatewayId == GATEWAY_BITPAY) {
            return PAYMENT_TYPE_BITCOIN;
        } else {
            return PAYMENT_TYPE_CREDIT_CARD;
        }
    }

    public static function getPrettyPaymentType($gatewayId) {
        return trans('texts.' . strtolower(Gateway::getPaymentType($gatewayId)));
    }
}
