<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Helpers\Crons;


use Toristy\Cores\Option;
use Toristy\Helpers\Cron;
use Toristy\Helpers\Process;

class SyncCron extends Cron
{
    protected $Running;
    private $Names = [
        'Current' => 'cron-current',
        'Time' => 'cron-time',
        'Count' => 'cron-count'
    ];
    private $Defaults = [
        'Current' => 0,
        'Time' => 0,
        'Count' => 1
    ];
    /**
     * @var string[]
     */
    private $Jobs;
    /**
     * @var string
     */
    private $Cache;
    /**
     * @var int
     */
    private $Current = 0;

    public function __construct(string $cache)
    {
        $this->Cache = $cache;
        parent::__construct('toristy_task');
        $this->Jobs = [
            'Categories',
            'Types',
            'Locations',
            'Providers',
            'Services'
        ];
        foreach ($this->Names as $name => $val) {
            $key = $this->Names[$name];
            $val = $this->Defaults[$name];
            $this->{$name} = (int)Option::Get($key, $val);
        }
        $this->Check();
        $this->Name = $this->Jobs[$this->Current];
    }

    private function Check()
    {
        if (!array_key_exists($this->Current, $this->Jobs)) {
            $this->Current = 0;
            $this->Update($this->Names['Current'], $this->Current);
            $this->Time = $this->GetStamp('+12hours');
            $this->Update($this->Names['Time'], $this->Time);
            $this->Count = 1;
            $this->Update($this->Names['Count'], $this->Count);
            $this->Running = !$this->Clear($this->RunName);
        }
    }

    protected function Process(): bool
    {
        $process = new Process($this->Token, $this->Cache);
        if (method_exists($process, $this->Name)) {
            $num = $process->{$this->Name}($this->Count);
            if ($num === 1) {
                $this->Update($this->Names['Count'], $this->Count + 1);
            } else if ($num === 0) {
                $this->Update($this->Names['Current'], ($this->Current + 1));
                $this->Update($this->Names['Count'], 1);
            }
        }
        Option::Set('cron-process', $this->Name);
        return $this->Clear($this->RunName);
    }
}