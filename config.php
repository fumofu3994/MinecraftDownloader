<?php
return [
    'turnstile_sitekey' => getenv('TURNSTILE_SITEKEY') ?: '',
    'turnstile_secret' => getenv('TURNSTILE_SECRET') ?: '',
    'bmclapi_base_url' => getenv('BMCLAPI_BASE_URL') ?: 'https://bmclapi2.bangbang93.com',
    'default_language' => getenv('DEFAULT_LANGUAGE') ?: 'zh-cn',
    'allowed_languages' => ['zh-cn', 'zh-tw', 'en'],
    'csrf_token_length' => 32,
    'debug' => getenv('DEBUG') ?: false,
    'hitokoto_api' => getenv('HITOKOTO_API') ?: 'https://v1.hitokoto.cn/?encode=text',
];