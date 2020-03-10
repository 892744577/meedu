<?php

namespace App\Providers;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeixinPhoneExtendSocialite{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'weixinphone', 'App\Providers\WeixinPhoneProvider'
        );
    }
}