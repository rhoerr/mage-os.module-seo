<?php

declare(strict_types=1);

namespace MageOS\Seo\Block\Widget;

use Magento\Widget\Block\BlockInterface;
use MageOS\Seo\Block\AbstractFaqElement;

/**
 * Widget that renders a FAQ group and contributes it to FAQPage structured data.
 *
 * Works as a classic widget (layout XML, widget instances, {{widget}} in CMS content) and inside
 * Page Builder via the generic CMS Widget content type, on any theme.
 */
class FaqList extends AbstractFaqElement implements BlockInterface
{
    /**
     * @var string
     */
    protected $_template = 'MageOS_Seo::faq/list.phtml';
}
