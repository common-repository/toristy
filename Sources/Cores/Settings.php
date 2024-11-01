<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores;


use Toristy\Helpers\Hook;

class Settings
{
    private $Customs = [
        "settings" => [],
        "fields"   => [],
        "sections" => []
    ];

    /**
     * Settings constructor.
     */
    public function __construct()
    {
        Hook::Add('setting-1','admin_init', [$this, 'Populate']);
    }

    /**
     * Add wordpress settings.
     *
     * @param  string  $group
     * @param  string  $name
     * @param  string  $callback
     */
    public function AddSetting(string $group, string $name, $callback = ""): void
    {
        $setting                     = [
            "option_group" => $group,
            "option_name"  => $name,
            "callback"     => $callback
        ];
        $this->Customs["settings"][] = $setting;
    }

    /**
     * Add settings sections
     *
     * @param  string  $id
     * @param  string  $title
     * @param  string  $page
     * @param  string  $callback
     */
    public function AddSection(string $id, string $title, string $page, $callback = ""): void
    {
        $section                     = [
            "id"       => $id,
            "title"    => $title,
            "callback" => $callback,
            "page"     => $page
        ];
        $this->Customs["sections"][] = $section;
    }

    /**
     * Add settings fields.
     *
     * @param  string  $id
     * @param  string  $title
     * @param  string  $page
     * @param  string  $section
     * @param  array  $args
     * @param  string  $callback
     */
    public function AddField(
        string $id,
        string $title,
        string $page,
        string $section,
        array $args,
        $callback = ""
    ): void {
        $field = [
            "id"       => $id,
            "title"    => $title,
            "callback" => $callback,
            "page"     => $page,
            "section"  => $section,
            "args"     => $args
        ];
        $this->Customs["fields"][] = $field;
    }

    public function Populate(): void
    {
        $settings = $this->Customs["settings"];
        $sections = $this->Customs["sections"];
        $fields   = $this->Customs["fields"];
        if (empty($settings)) {
            return;
        }
        // register setting
        foreach ($settings as $setting) {
            register_setting(
                $setting["option_group"],
                $setting["option_name"],
                (isset($setting["callback"]) ? $setting["callback"] : '')
            );
        }

        // add settings section
        foreach ($sections as $section) {
            add_settings_section(
                $section["id"],
                $section["title"],
                (isset($section["callback"]) ? $section["callback"] : ''),
                $section["page"]
            );
        }

        // add settings field
        foreach ($fields as $field) {
            add_settings_field(
                $field["id"],
                $field["title"],
                (isset($field["callback"]) ? $field["callback"] : ''),
                $field["page"],
                $field["section"],
                (isset($field["args"]) ? $field["args"] : '')
            );
        }
    }
}