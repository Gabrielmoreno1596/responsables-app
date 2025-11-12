<?php

$token = (string)($b['captcha_token'] ?? '');
if ($token === '') return ResponseHelper::json($res, ['error' =>
'captcha_required'], 400);
$secret = getenv('RECAPTCHA_SECRET') ?:
    'TU_SECRET_KEY';
$verify =
    file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" .
        urlencode($secret) . "&response=" . urlencode($token));
$vr =
    json_decode($verify, true);
if (!($vr['success'] ?? false) || ($vr['score'] ??
    0) < 0.5) {
    return ResponseHelper::json(
        $res,
        ['error' => 'captcha_failed'],
        403|
    );
}