<?php

namespace App\Services;

use League\CommonMark\CommonMarkConverter;

class MarkdownRenderer
{
    private CommonMarkConverter $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function render(string $markdown): string
    {
        $html = $this->converter->convert($markdown);

        return $this->purify($html);
    }

    private function purify(string $html): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'h1,h2,h3,h4,h5,h6,p,br,strong,em,del,blockquote,code,pre,ul,ol,li,a[href|title],img[src|alt|width|height],table,thead,tbody,tr,th,td,hr,sup,sub');
        $config->set('AutoFormat.RemoveEmpty', true);
        $purifier = new \HTMLPurifier($config);

        return $purifier->purify($html);
    }
}
