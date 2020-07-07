<?php

/*
 * This file is part of the Qsnh/meedu.
 *
 * (c) XiaoTeng <616896861@qq.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Listeners\PaymentSuccessEvent;

use App\Events\PaymentSuccessEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\Member\Services\NotificationService;
use App\Services\Member\Interfaces\NotificationServiceInterface;

/**
 * Class OrderPaidNotificationListener
 * @package App\Listeners\PaymentSuccessEvent
 * 该类启用了队列功能ShouldQueue，若服务器没用启用supervisorctl队列处理程序，就会执行时被忽略
 */
class OrderPaidNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @var NotificationService
     */
    protected $notificationService;

    public function __construct(NotificationServiceInterface $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * @param $event PaymentSuccessEvent
     */
    public function handle(PaymentSuccessEvent $event)
    {
        $order = $event->order;
        $this->notificationService->notifyOrderPaidMessage($order['user_id'], $order['order_id']);
    }
}
