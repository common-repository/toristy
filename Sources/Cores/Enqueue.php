<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores;


use Toristy\Helpers\Cron;
use Toristy\Helpers\Domain;
use Toristy\Helpers\Hook;

class Enqueue
{
    /**
     * @var Skin
     */
    private $Skin;

    /**
     * @var Category
     */
    private $Category;

    /**
     * @var Page
     */
    private $Page;

    private $Names = [
        "toristy-page",
        "toristy-service",
        "toristy-category",
        "toristy-type",
        "toristy-location"
    ];

    private $Taxes = [
        "toristy-category",
        "toristy-type",
        "toristy-location"
    ];

    /**
     * @var array
     */
    private $Settings = [
        'color' => '186b6d',
        'bg-color' => 'ebf3f5',
        'txt-color' => 'ffffff'
    ];

    public function __construct(Skin $skin, Page $page, Category $category)
    {
        $this->Skin = $skin;
        $this->Category = $category;
        $this->Page = $page;
        Hook::Add('enqueue-1', 'admin_enqueue_scripts', [$this, 'Back']);
        Hook::Add('enqueue-2', 'wp_enqueue_scripts', [$this, 'Front']);
        Hook::Add('enqueue-3', 'enqueue_block_editor_assets', [$this, 'Blocks']);
    }

    public function Blocks()
    {
        wp_enqueue_script( 'toristy-blocks', Domain::Url('assets/js/blocks-min.js'), [
            'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-api-fetch'
        ] );
        wp_enqueue_style('toristy-blocks', Domain::Url('assets/css/editor-min.css'));
    }

    public function Front()
    {
        global $post;
        if (is_null($post)) { return; }
        $name = $this->Page->GetSkin($post);
        if ($name !== '') {
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-slider');
            /*wp_enqueue_script('jquery-ui-autocomplete', '', ['jquery-ui-widget', 'jquery-ui-position'], '1.8.6');*/
            $this->Auto($name);
            wp_enqueue_style( 'toristy', Domain::Url('assets/css/toristy-min.css') );
            wp_add_inline_style('toristy', $this->Styles());
            wp_enqueue_script( 'toristy', Domain::Url('assets/js/toristy-min.js'), [], false, true  );
            $this->Sync();
        }
        wp_enqueue_style('toristy-blocks', Domain::Url('assets/css/blocks-min.css'));
    }

    private function AutoLoad(string $name) {
        if ($name === 'toristy-page') {
            // we give these values to the javascript
            $value = array(
                'url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce('ajax_nonce')
            );
            // enqueue autocomplete the wordpress way
            wp_enqueue_script('jquery-ui-autocomplete', '', array('jquery-ui-widget', 'jquery-ui-position'), '1.12.1');
            wp_enqueue_script( 'toristy-search-js', Domain::Url('assets/js/main.js'), array('jquery', 'jquery-ui-autocomplete'), false, 1  );
            // with localize we hook the value in toristy_search to the script
            wp_localize_script('toristy-search-js', 'toristy_search', $value);
        }
    }

    private function Auto(string $name)
    {
        if ($name === 'toristy-page' || $name === 'toristy-provider') {
            wp_enqueue_style(
                'jquery-auto-complete',
                Domain::Url('assets/css/jquery.auto-complete.css'),
                [], '1.0.7'
            );

            wp_enqueue_script(
                'jquery-auto-complete',
                Domain::Url('assets/js/jquery.auto-complete.min.js'),
                ['jquery'], '1.0.7', true
            );

            wp_enqueue_script(
                'toristy-search',
                Domain::Url('assets/js/search-min.js'),
                ['jquery'], '', true
            );

            wp_localize_script(
                'toristy-search',
                'toristySearch',
                [
                    'url' => admin_url( 'admin-ajax.php' ),
                    'nonce' => wp_create_nonce('ajax_nonce')
                ]
            );
        }
    }

    private function Styles(): string
    {
        $this->Settings = array_merge($this->Settings, (array)Option::Get('toristy_pages', [], true));
        list('color' => $color, 'bg-color' => $bgColor, 'txt-color' => $txtColor) = $this->Settings;
        return ".toristy-category-item.selected a, .toristy-category-item a:hover, .toristy-random-items a:hover, .toristy-pagination a:hover, .toristy-pagination .current {
                border-color: #{$color} !important;
                color: #{$color} !important;
            }
            .toristy-btn {
                border-color: #{$color} !important;
                background-color: #{$color} !important;
            }
            .toristy-home-info {
                background-color: #{$bgColor} !important;
                color: #{$txtColor} !important;
            }
            .toristy-home-info h2 {
                color: #{$txtColor} !important;
            }
            .toristy-search-filter {
                background-color: #{$color} !important;
            }
            .toristy-category-pedal::before, .toristy-pagination .prev.disabled::before, .toristy-pagination .next.disabled::before {
                border-color: #{$color} !important;
            }";
    }

    public function Back($name)
    {
        $other = basename($_SERVER['REQUEST_URI']);
        if (isset($name) && $name === "toplevel_page_toristy-settings" || strpos($other, "toristy") !== false)
        {
            wp_enqueue_media();
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_style( 'toristy', Domain::Url('assets/css/admin-min.css') );
            wp_enqueue_script( 'iris', admin_url( 'js/iris.min.js' ), array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ), false, 1 );
            wp_enqueue_script( 'toristy', Domain::Url('assets/js/admin-min.js'), ['wp-color-picker'], false, false);
            wp_localize_script(
                'toristy',
                'toristySync',
                [
                    'url' => admin_url( 'admin-ajax.php' ),
                    'nonce' => wp_create_nonce('ajax_nonce')
                ]
            );
        }
        $this->Sync();
    }

    private function Sync()
    {
        $cron = Plugin::Get('cron');
        if ($cron instanceof Cron) {
            $cron->Dispatch();
        }
    }
}