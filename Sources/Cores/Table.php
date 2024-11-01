<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores;


use Exception;

class Table
{
    private $Db;

    private $Name;

    private $Prefix;

    private $Tables = [];

    public function __construct()
    {
        $this->Name = Table::class;
        global $wpdb;
        $this->Db = $wpdb;
        $this->Prefix = $wpdb->prefix. "toristy_";
        $this->Tables = (array)Option::Get($this->Name, []);
    }

    /**
     * @param  string  $table  table name add own prefix if needed.(no need to add wordpress prefix).
     * @param  array  $data  key value pair.
     * @param  array  $where  Condition to perform with the query.
     * @param  string  $order  how to sort the result.
     *
     * @return object|null
     */
    public function Get(string $table, array $data = [], array $where = [], string $order = "") : ?object
    {
        $query = "SELECT ";
        $query .= (!empty($data)) ? implode(", ", $data) : "*";
        $query .= " FROM $this->Prefix$table";
        $bol = false;
        foreach ($where as $key => $val)
        {
            if (!$bol)
            {
                $bol = true;
                $query .=" WHERE";
            }
            $query .= " $key = '$val'";
        }
        if (isset($order) && $order !== "")
        {
            $query = trim($query)." ORDER BY $order";
        }
        return $this->Db->get_results(trim($query).";");
    }

    /**
     * @param  string  $table table name add own prefix if needed.(no need to add wordpress prefix).
     * @param  array  $data key value pair, key must be table column names.
     *
     * @return int
     */
    public function Insert(string $table, array $data) : int
    {
        $this->Db->insert("$this->Prefix$table", $data);
        return $this->Db->insert_id;
    }

    /**
     * @param  string  $table table name add own prefix if needed.(no need to add wordpress prefix).
     * @param  array  $data key value pair, key must be table column names.
     * @param  array  $where Condition to perform with the query.
     *
     * @return bool|false|int
     */
    public function Update(string $table, array $data, array $where)
    {
        if (empty($data) || empty($where)) { return false; }
        return $this->Db->update("$this->Prefix$table", $data, $where);
    }

    /**
     * @param  string  $table table name add own prefix if needed.(no need to add wordpress prefix).
     * @param  array  $where Condition to perform with the query.
     *
     * @return bool|false|int
     */
    public function Delete(string $table, array $where)
    {
        if (empty($where)) { return false; }
        return $this->Db->delete("$this->Prefix$table", $where);
    }

    /**
     * @param  string  $table table name to create
     * @param  string  $columns table column names and with comma.
     *
     * @return bool
     */
    public function Create(string $table, string $columns): bool
    {
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        try
        {
            $sql = "CREATE TABLE IF NOT EXISTS $this->Prefix$table($columns);";
            $result = dbDelta($sql);
            //var_dump($result);
            if ($result && !in_array($table, $this->Tables)) {
                $this->Tables[] = $table;
            }
        }
        catch (Exception $e) { }
        return false;
    }

    /**
     * Drop all cached table names created through this class.
     */
    public function DropAll(): void
    {
        if (empty($this->Tables)) { return; }
        foreach ($this->Tables as $table)
        {
            $this->Drop($table);
        }
    }

    /**
     * @param  string  $table table name to drop
     *
     * @return bool
     */
    public function Drop(string $table): bool
    {
        $this->Db->query("SET FOREIGN_KEY_CHECKS=0;");
        $sql = "DROP TABLE IF EXISTS $this->Prefix$table;";
        $bol = $this->Db->query($sql);
        $this->Db->query("SET FOREIGN_KEY_CHECKS=1;");
        $pos = array_search($table, $this->Tables);
        //var_dump($pos, $bol);
        if ($bol && $pos) {
            unset($this->Tables[$pos]);
        }
        return $bol;
    }

    /**
     * Truncate all table names created through this class.
     */
    public function TruncateAll():void
    {
        if (empty($this->Tables)) { return; }
        foreach ($this->Tables as $table)
        {
            $this->Truncate($table);
        }
    }

    /**
     * table have to be created through this class to work
     * @param string $table table name
     * @return bool|int
     */
    public function Truncate(string $table)
    {
        if (in_array($table, $this->Tables)) {
            $this->Db->query("SET FOREIGN_KEY_CHECKS=0;");
            $sql = "TRUNCATE TABLE $this->Prefix$table;";
            $bol = $this->Db->query($sql);
            $this->Db->query("SET FOREIGN_KEY_CHECKS=1;");
            return $bol;
        }
        return false;
    }

    public function __destruct()
    {
        Option::Set($this->Name, $this->Tables);
    }
}