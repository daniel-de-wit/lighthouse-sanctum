<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Provider
    |--------------------------------------------------------------------------
    |
    | Configure the User Provider used to authenticate the user.
    | See `auth.providers` configuration
    |
    */
    'provider' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Use signed email verification URL
    |--------------------------------------------------------------------------
    |
    | Whether or not to sign the email verification URL
    | like the standard Laravel implementation does.
    | If set to `true`, additional `expires` and `signature` parameters
    | will be added to the URL. When verifying the email through the API
    | both those fields are required as well.
    | It defaults to `false` for backwards compatibility.
    |
    */
    'use_signed_email_verification_url' => false,
];
