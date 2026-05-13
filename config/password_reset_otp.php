<?php

return [

    'otp_ttl_minutes' => (int) env('PASSWORD_RESET_OTP_TTL', 15),

    'reset_token_ttl_minutes' => (int) env('PASSWORD_RESET_TOKEN_TTL', 30),

    'otp_length' => 6,

];
