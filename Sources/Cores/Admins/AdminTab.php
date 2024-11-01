<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores\Admins;


use Toristy\Cores\Option;
use Toristy\Cores\Settings;

abstract class AdminTab
{
    /**
     * @var Settings
     */
    protected $Settings;
    /**
     * @var string
     */
    protected $Option;
    protected $Options;
    protected $Title = '';
    /**
     * @var string
     */
    private $Name;
    /**
     * @var string
     */
    private $Key;
    /**
     * @var array
     */
    private $Datas = [];
    /**
     * @var array
     */
    private $Tabs;

    protected $Extras = [];
    /**
     * @var bool
     */
    protected $Bol = false;

    protected function __construct(Settings $settings, string $option, $value, string $name, string $key)
    {
        $this->Settings = $settings;
        $this->Name = "toristy-$name";
        $this->Key = "toristy-$key";
        $this->Options = Option::Get($option, $value, true);
        $this->Option = $option;
        $this->Tabs = [
            $name, $key, $this->Title, null, implode('', []), $this->Bol
        ];
        $this->Start();
    }

    private function Start(): void
    {
        $this->Populate();
        $this->Create();
    }

    protected function Extra() {

    }

    protected abstract function Populate();

    private function Create()
    {
        $sections = [];
        $this->Settings->AddSetting($this->Key, $this->Option, [$this, "Group"]);
        $sectionId = '';
        foreach ($this->Datas as $section => $datas) {
            if (!in_array($section, $sections)) {
                $sections[] = $section;
                $title = ($section === 'empty') ? '' : $section;
                $sectionId = (strlen($title) > 0) ? sanitize_title_with_dashes($title) : $this->Name;
                $this->Settings->AddSection($sectionId, $title, "$this->Key", function () use ($title) {
                    $this->Section($title);
                });
            }
            foreach ($datas as $data) {
                list($id, $title, $values) = $data;
                $name = $this->Name.'-'.$id;
                $values = array_merge($values, ['class' => $name]);
                $this->Settings->AddField($name, $title, $this->Key, $sectionId, $values, [$this, 'Load']);
            }
        }
    }

    /**
     * @return string
     */
    public function GetOptionKey(): string
    {
        return $this->Option;
    }

    public function GetTabs(): array
    {
        return $this->Tabs;
    }

    public abstract function Load($args);

    public function Group($inputs)
    {
        return $inputs;
    }

    public function Section(string $title = '')
    {
    }

    protected function Add(string $id, string $title, array $datas, string $section = 'empty'): void
    {
        $this->Datas[$section][] = [$id, $title, $datas];
    }
}