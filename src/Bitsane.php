<?php

namespace Bitsane;

class Bitsane
{
    const ORDER_TYPE_LIMIT = 'limit';
    const ORDER_TYPE_MARKET = 'market';

	protected $key;
	protected $private;
	protected $api = 'https://bitsane.com/api';
    public $errno;
    public $error;

	public function __construct($key, $private)
	{
		$this->key = $key;
		$this->private = $private;
	}

	protected function request($method, $params = [], $public = true)
	{
	    $url = $this->api . (!$public ? '/private/' : '/public/') . $method;
	    if ($public && !empty($params)) {
	    	$url .= '?' . http_build_query($params);
	    }

	    $ch = curl_init($url);
	    if (!$ch) return null;

	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($ch, CURLOPT_HEADER, true);

	    if (!$public) {
	    	$params['nonce'] = microtime(true) * 1000000;
    		$json = json_encode($params);
    		$json_enc = base64_encode($json);
    		$signature = hash_hmac('sha384', $json_enc, $this->private);
	    	curl_setopt($ch, CURLOPT_POST, true);
    		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_enc);
    		curl_setopt($ch, CURLOPT_HTTPHEADER, [
    			"Content-Type: text/plain",
    			"X-BS-APIKEY: {$this->key}",
    			"X-BS-PAYLOAD: {$json_enc}",
    			"X-BS-SIGNATURE: {$signature}",
    		]);
	    }

	    $res = curl_exec($ch);
	    if (curl_errno($ch) != 0 || curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
	        return false;
	    }

	    $curl_info = curl_getinfo($ch);   
	    $header_size = $curl_info['header_size'];
	    $header = substr($res, 0, $header_size);
	    $body = substr($res, $header_size);
	    
	    if(strpos($header, 'Content-Encoding: gzip') !== false) {
	        $body = gzdecode($body);
	    }

        $data = json_decode($body, true);
        if ($public) {
            return $data;
        } else {
            if ($data['statusCode'] > 0) {
                $this->errno = $data['statusCode'];
                $this->error = $data['statusText'];
                return null;
            }
            return $data['result'];
        }
    }

	public function ticker($pairs = null) 
	{
		$data = $this->request('ticker', is_null($pairs) ? [] : ['pairs' => $pairs]);
		return $data;
	}

	public function orderbook($pair, $limit_bids = 50, $limit_asks = 50) 
	{
		$data = $this->request('orderbook', ['pair' => $pair, 'limit_bids' => $limit_bids, 'limit_asks' => $limit_asks]);
		return $data;
	}

	public function trades($pair, $since = 50, $limit = 50) 
	{
		$data = $this->request('trades', ['pair' => $pair, 'since' => $since, 'limit' => $limit]);
		return $data;
	}

	public function currencies() 
	{
		$data = $this->request('assets/currencies');
		return $data;
	}

	public function pairs() 
	{
		$data = $this->request('assets/pairs');
		return $data;
	}

	public function balances()
	{
		$data = $this->request('balances', [], false);
		return $data;
	}

	public function depositAddress($currency)
	{
		$data = $this->request('deposit/address', ['currency' => $currency], false);
		return $data;
	}

	public function transactionsHistory($currency, $since, $until = null, $limit = null)
	{
		$params = ['currency' => $currency, 'since' => $since];
		if (!is_null($until)) $params['until'] = $until;
		if (!is_null($limit)) $params['limit'] = $limit;
		$data = $this->request('transactions/history', $params, false);
		return $data;
	}

	public function vouchers()
	{
		$data = $this->request('vouchers', [], false);
		return $data;
	}

	public function createVoucher($currency, $amount, $pin)
	{
		$params = ['currency' => $currency, 'amount' => $amount, 'pin' => $pin];
		$data = $this->request('vouchers/create', $params, false);
		return $data;
	}

	public function redeemVoucher($voucher, $pin)
	{
		$params = ['voucher' => $voucher, 'pin' => $pin];
		$data = $this->request('vouchers/redeem', $params, false);
		return $data;
	}

	public function withdraw($currency, $amount, $address, $additional = null)
	{
		$params = ['currency' => $currency, 'amount' => $amount, 'address' => $address];
		if (!is_null($additional)) $params['additional'] = $additional;
		$data = $this->request('withdraw', $params, false);
		return $data;
	}

	public function withdrawalStatus($withdrawal_id)
	{
		$params = ['withdrawal_id ' => $withdrawal_id];
		$data = $this->request('withdrawal/status', $params, false);
		return $data;
	}

	public function orders()
	{
		$params = [];
		$data = $this->request('orders', $params, false);
		return $data;
	}

	public function ordersHistory($pair, $since, $until = null, $limit = null, $reverse = false)
	{
		$params = ['pair' => $pair, 'since' => $since];
		if (!is_null($until)) $params['until'] = $until;
		if (!is_null($limit)) $params['limit'] = $limit;
		if (!is_null($reverse)) $params['reverse'] = $reverse;
		$data = $this->request('orders/history', $params, false);
		return $data;
	}

	public function orderNew($pair, $amount, $price, $side, $type = ORDER_TYPE_LIMIT, $hidden = false)
	{
		$params = ['pair' => $pair, 'amount' => $amount, 'price' => $price, 'side' => $side, 'type' => $type, 'hidden' => $hidden];
		$data = $this->request('order/new', $params, false);
		return $data;
	}

	public function orderStatus($order_id)
	{
		$params = ['order_id' => $order_id];
		$data = $this->request('order/status', $params, false);
		return $data;
	}

	public function orderCancel($order_id)
	{
		$params = ['order_id' => $order_id];
		$data = $this->request('order/cancel', $params, false);
		return $data;
	}
}
