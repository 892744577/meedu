<?php

/*
 * This file is part of the Qsnh/meedu.
 *
 * (c) XiaoTeng <616896861@qq.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Http\Controllers\Frontend;

use App\Constant\FrontendConstant;
use App\Exceptions\ServiceException;
use App\Exceptions\SystemException;
use App\Services\Order\Interfaces\OrderServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Businesses\BusinessState;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BaseController;
use App\Services\Member\Services\UserService;
use App\Services\Course\Services\VideoService;
use App\Services\Course\Services\CourseService;
use App\Services\Order\Services\PromoCodeService;
use App\Services\Course\Services\VideoCommentService;
use App\Services\Course\Services\CourseCommentService;
use App\Services\Member\Interfaces\UserServiceInterface;
use App\Services\Course\Interfaces\VideoServiceInterface;
use App\Services\Course\Interfaces\CourseServiceInterface;
use App\Services\Order\Interfaces\PromoCodeServiceInterface;
use App\Services\Course\Interfaces\VideoCommentServiceInterface;
use App\Http\Requests\Frontend\CourseOrVideoCommentCreateRequest;
use App\Services\Course\Interfaces\CourseCommentServiceInterface;
use Illuminate\Support\Facades\Log;

class AjaxController extends BaseController
{
    /**
     * @var VideoCommentService
     */
    protected $videoCommentService;
    /**
     * @var CourseCommentService
     */
    protected $courseCommentService;
    /**
     * @var UserService
     */
    protected $userService;
    /**
     * @var CourseService
     */
    protected $courseService;
    /**
     * @var VideoService
     */
    protected $videoService;
    /**
     * @var PromoCodeService
     */
    protected $promoCodeService;
    protected $businessState;
    protected $orderService;

    public function __construct(
        VideoCommentServiceInterface $videoCommentService,
        CourseCommentServiceInterface $courseCommentService,
        UserServiceInterface $userService,
        VideoServiceInterface $videoService,
        CourseServiceInterface $courseService,
        PromoCodeServiceInterface $promoCodeService,
        BusinessState $businessState,
        OrderServiceInterface $orderService
    ) {
        $this->videoCommentService = $videoCommentService;
        $this->courseCommentService = $courseCommentService;
        $this->userService = $userService;
        $this->videoService = $videoService;
        $this->courseService = $courseService;
        $this->promoCodeService = $promoCodeService;
        $this->businessState = $businessState;
        $this->orderService = $orderService;
    }

    /**
     * 课程评论.
     *
     * @param CourseOrVideoCommentCreateRequest $request
     * @param $courseId
     *
     * @return array
     */
    public function courseCommentHandler(CourseOrVideoCommentCreateRequest $request, $courseId)
    {
        $course = $this->courseService->find($courseId);
        ['content' => $content] = $request->filldata();
        $comment = $this->courseCommentService->create($course['id'], $content);
        $user = $this->userService->find(Auth::id(), ['role']);

        return $this->data([
            'content' => $comment['render_content'],
            'created_at' => Carbon::parse($comment['created_at'])->diffForHumans(),
            'user' => [
                'nick_name' => $user['nick_name'],
                'avatar' => $user['avatar'],
                'role' => $user['role'] ? $user['role']['name'] : '免费会员',
            ],
        ]);
    }

    /**
     * 视频评论.
     *
     * @param CourseOrVideoCommentCreateRequest $request
     * @param $videoId
     *
     * @return array
     */
    public function videoCommentHandler(CourseOrVideoCommentCreateRequest $request, $videoId)
    {
        $video = $this->videoService->find($videoId);
        ['content' => $content] = $request->filldata();
        $comment = $this->videoCommentService->create($video['id'], $content);
        $user = $this->userService->find(Auth::id(), ['role']);

        return $this->data([
            'content' => $comment['render_content'],
            'created_at' => Carbon::parse($comment['created_at'])->diffForHumans(),
            'user' => [
                'nick_name' => $user['nick_name'],
                'avatar' => $user['avatar'],
                'role' => $user['role'] ? $user['role']['name'] : '免费会员',
            ],
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function promoCodeCheck(Request $request)
    {
        $promoCode = $request->input('promo_code');
        if (!$promoCode) {
            return $this->error(__('error'));
        }
        $code = $this->promoCodeService->findCode($promoCode);
        if (!$code) {
            return $this->error(__('promo code not exists'));
        }
        if ($code['expired_at'] && Carbon::now()->gt($code['expired_at'])) {
            return $this->error(__('promo code has expired'));
        }
        if (!$this->businessState->promoCodeCanUse($code)) {
            return $this->error(__('user cant use this promo code'));
        }
        return $this->data([
            'id' => $code['id'],
            'discount' => $code['invited_user_reward'],
        ]);
    }

    /**
     * @param Request $request
     * @param $orderId
     * @return mixed
     * @throws SystemException
     * @throws \App\Exceptions\ServiceException
     */
    public function payByMp(Request $request, $orderId)
    {
        $order = $this->orderService->findUserNoPaid($orderId);

        $scene = is_h5() ? FrontendConstant::PAYMENT_SCENE_H5 : FrontendConstant::PAYMENT_SCENE_PC;
        Log::info($scene);
        $payments = get_payments($scene);
        Log::info($payments);
        $payment = $order['payment'] ?: $request->post('payment');
        Log::info($payment);
        if (!$payment) {
            throw new ServiceException(__('payment not exists'));
        }
        $paymentMethod = $payments[$payment][$scene] ?? '';
        if (!$paymentMethod) {
            throw new SystemException(__('payment method not exists'));
        }

        // 更新订单的支付方式
        $updateData = [
            'payment' => $payment,
            'payment_method' => $paymentMethod,
        ];
        $this->orderService->change2Paying($order['id'], $updateData);
        $order = array_merge($order, $updateData);

        // 创建远程订单
        $paymentHandler = app()->make($payments[$payment]['handler']);
        $createResult = $paymentHandler->createByMp($order);
        if ($createResult->status == false) {
            throw new SystemException(__('remote order create failed'));
        }

        return $this->data($createResult->data);
    }
}
