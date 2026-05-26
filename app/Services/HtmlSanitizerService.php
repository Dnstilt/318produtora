<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizerService
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $cachePath = storage_path('app/purifier');
        if (!is_dir($cachePath)) {
            @mkdir($cachePath, 0755, true);
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', $cachePath);
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed', implode(',', [
            'p',
            'br',
            'strong',
            'b',
            'em',
            'i',
            'u',
            'ul',
            'ol',
            'li',
            'a[href|target|rel]',
            'blockquote',
            'h2',
            'h3',
            'h4',
        ]));
        $config->set('Attr.AllowedFrameTargets', ['_blank', '_self']);
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
        ]);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.RemoveSpansWithoutAttributes', true);
        $config->set('HTML.ForbiddenElements', ['script', 'style', 'iframe', 'object', 'embed']);
        $config->set('Attr.EnableID', false);
        $config->set('HTML.Nofollow', true);
        $config->set('HTML.TargetBlank', true);

        $this->purifier = new HTMLPurifier($config);
    }

    public function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return $this->purifier->purify($html);
    }
}
