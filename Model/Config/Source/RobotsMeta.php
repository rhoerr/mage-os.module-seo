<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class RobotsMeta implements OptionSourceInterface
{
    /**
     * Return robots meta options for system.xml select fields.
     *
     * @return mixed[]
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '',                 'label' => (string) __('Use Magento Default (no override)')],
            ['value' => 'INDEX,FOLLOW',     'label' => 'INDEX, FOLLOW'],
            ['value' => 'NOINDEX,FOLLOW',   'label' => 'NOINDEX, FOLLOW'],
            ['value' => 'INDEX,NOFOLLOW',   'label' => 'INDEX, NOFOLLOW'],
            ['value' => 'NOINDEX,NOFOLLOW', 'label' => 'NOINDEX, NOFOLLOW'],
            [
                'value' => 'INDEX,FOLLOW,max-image-preview:large,max-snippet:-1',
                'label' => 'INDEX, FOLLOW (rich previews: max-image-preview:large, max-snippet:-1)',
            ],
            ['value' => 'NOINDEX,FOLLOW,noarchive',   'label' => 'NOINDEX, FOLLOW, noarchive'],
            [
                'value' => 'NOINDEX,NOFOLLOW,noai,noimageai',
                'label' => 'NOINDEX, NOFOLLOW, noai, noimageai (block AI training)',
            ],
        ];
    }
}
