<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores\Codes;


use Toristy\Cores\ShortCode;

class TagCode extends ShortCode
{
    private $Tags = [
        'div' => true, 'p' => true, 'span' => true, 'section' => true, 'script' => true,
        'input' => false, 'textarea' => true, 'img' => false
    ];

    public function __construct()
    {
        parent::__construct('tags');
    }

    public function Render($params = [], $content = null): string
    {
        $attrs = [];
        $content = (is_string($content) && strlen($content) > 0) ? $content : ((isset($params['content']) && is_string($params['content'])) ? $params['content'] : '');
        if (is_admin()) { return ''; }
        $this->Class($params, $attrs);
        $tag = $this->Tag($params);
        $this->Styles($params, $attrs);
        if (isset($content) && strpos($content, '[toristy-code-') !== false) {
            $temps = explode("[toristy-code-", $content);
            $content = '';
            foreach ($temps as $temp) {
                if (strlen($temp) > 0) {
                    $content .= do_shortcode("[toristy-code-$temp");
                }
            }

        }
        $attr = implode(' ', $attrs);
        if (strlen($attr) > 0) {
            $attr = ' ' . $attr;
        }
        return ($this->Tags[$tag]) ? "<$tag$attr>$content</$tag>" : "<$tag$attr/>";
    }

    private function Class(array &$params, array &$attrs): void
    {
        $class = (isset($params['class'])) ? $params['class'] : "";
        unset($params['class']);
        if (strlen($class) > 0) {
            $attrs[] = "class='$class'";
        }
    }

    private function Tag(array &$attrs = []): string
    {
        $tag = (isset($attrs['type']) && array_key_exists($attrs['type'], $this->Tags)) ? strtolower($attrs['type']) : "div";
        unset($attrs['type']);
        return $tag;
    }

    private function Styles(array &$params, array &$attrs): void
    {
        $styles = [];
        $keys = array_keys($params);
        foreach ($keys as $key) {
            if (strpos($key, 'css_') !== false && isset($params[$key]) && is_string($params[$key]) && strlen($params[$key]) > 0) {
                $styles[] = substr($key, 4) . ':' . $params[$key];
                unset($params[$key]);
            }
        }
        if (!empty($styles)) {
            $style = implode('; ', $styles);
            $attrs[] = "style='$style'";
        }
    }
}