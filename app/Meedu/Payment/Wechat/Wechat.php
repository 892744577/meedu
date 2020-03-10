<?php

/*
 * This file is part of the Qsnh/meedu.
 *
 * (c) XiaoTeng <616896861@qq.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Meedu\Payment\Wechat;

use App\Services\Member\Services\SocialiteService;
use Exception;
use Yansongda\Pay\Pay;
use App\Businesses\BusinessState;
use App\Constant\FrontendConstant;
use App\Events\PaymentSuccessEvent;
use Illuminate\Support\Facades\Log;
use App\Meedu\Payment\Contract\Payment;
use App\Services\Base\Services\CacheService;
use App\Meedu\Payment\Contract\PaymentStatus;
use App\Services\Base\Services\ConfigService;
use App\Services\Order\Services\OrderService;
use App\Services\Base\Interfaces\CacheServiceInterface;
use App\Services\Base\Interfaces\ConfigServiceInterface;
use App\Services\Order\Interfaces\OrderServiceInterface;
use App\Services\Member\Interfaces\SocialiteServiceInterface;

class Wechat implements Payment
{
    /**
     * @var ConfigService
     */
    protected $configService;
    /**
     * @var OrderService
     */
    protected $orderService;
    /**
     * @var CacheService
     */
    protected $cacheService;
    protected $businessState;
    /**
     * @var SocialiteService
     */
    protected $socialiteService;

    public function __construct(
        ConfigServiceInterface $configService,
        OrderServiceInterface $orderService,
        CacheServiceInterface $cacheService,
        BusinessState $businessState,
        SocialiteServiceInterface $socialiteService
    ) {
        $this->configService = $configService;
        $this->orderService = $orderService;
        $this->cacheService = $cacheService;
        $this->businessState = $businessState;
        $this->socialiteService = $socialiteService;
    }

    public function create(array $order, array $extra = []): PaymentStatus
    {
        $total = $this->businessState->calculateOrderNeedPaidSum($order);
        try {
            $payOrderData = [
                'out_trade_no' => $order['order_id'],
                'total_fee' => $total * 100,
                'body' => $order['order_id'],
                'openid' => '',
            ];
            $payOrderData = array_merge($payOrderData, $extra);
            $createResult = Pay::wechat($this->configService->getWechatPay())->{$order['payment_method']}($payOrderData);
            Log::info(__METHOD__, compact('createResult'));

            // 缓存保存
            $this->cacheService->put(
                sprintf(FrontendConstant::PAYMENT_WECHAT_PAY_CACHE_KEY, $order['order_id']),
                $createResult,
                FrontendConstant::PAYMENT_WECHAT_PAY_CACHE_EXPIRE
            );

            // 构建Response
            $response = redirect(route('order.pay.wechat', [$order['order_id']]));

            return new PaymentStatus(true, $response);
        } catch (Exception $exception) {
            exception_record($exception);

            return new PaymentStatus(false);
        }
    }

    public function createByMp(array $order, array $extra = []): PaymentStatus
    {
        //$total = $this->businessState->calculateOrderNeedPaidSum($order);
        $total = 0.01;
        $openid = $this->socialiteService->userSocialites($order['user_id']);
        Log::info($openid);
        try {
            $payOrderData = [
                'out_trade_no' => $order['order_id'],
                'total_fee' => $total * 100,
                'body' => $order['order_id'],
                'openid' => 'oI0Os1ZwxDl1ZmUarUBUgVh6KV5g',
            ];
            $payOrderData = array_merge($payOrderData, $extra);
            $createResult = Pay::wechat($this->configService->getWechatPay())->{$order['payment_method']}($payOrderData);
            Log::info(__METHOD__, compact('createResult'));

            // 缓存保存
            $this->cacheService->put(
                sprintf(FrontendConstant::PAYMENT_WECHAT_PAY_CACHE_KEY, $order['order_id']),
                $createResult,
                FrontendConstant::PAYMENT_WECHAT_PAY_CACHE_EXPIRE
            );
            return new PaymentStatus(true, $createResult);
        } catch (Exception $exception) {
            exception_record($exception);

            return new PaymentStatus(false);
        }
    }

    /**
     * @param array $order
     *
     * @return PaymentStatus
     */
    public function query(array $order): PaymentStatus
    {
    }

    /**
     * @return mixed|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Yansongda\Pay\Exceptions\InvalidArgumentException
     */
    public function callback()
    {
        $pay = Pay::wechat($this->configService->getWechatPay());

        try {
            $data = $pay->verify();
            Log::info($data);

            $order = $this->orderService->findOrFail($data['out_trade_no']);

            event(new PaymentSuccessEvent($order));

            return $pay->success();
        } catch (Exception $e) {
            exception_record($e);

            return $e->getMessage();
        }
    }

    /**
     * @param array $order
     *
     * @return string
     */
    public static function payUrl(array $order): string
    {
        return route('order.pay.wechat', [$order['order_id']]);
    }
}
