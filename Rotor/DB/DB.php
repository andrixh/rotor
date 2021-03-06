<?php
namespace Rotor\DB;


class DB
{
    // Connection Vars
    const OBJECT = 'OBJECT';
    const ASSOC = 'ASSOC';
    const NUM = 'NUM';

    const OP_QUERY = 'OP_QUERY';
    const OP_GET_RESULTS = 'OP_GET_RESULTS';
    const OP_GET_ROW = 'OP_GET_ROW';
    const OP_GET_VAR = 'OP_GET_VAR';
    const OP_GET_COL = 'OP_GET_COL';

    private static $_db_host;
    private static $_db_name;
    private static $_db_user;
    private static $_db_pass;

    /**
     * @var \mysqli
     */
    private static $_mysqli = null;
    private static $_connected = false;

    private static $_error = false;
    private static $_affected_rows;
    private static $_insert_id;

    public static function __Init($host, $user, $pass, $db )
    {
        _g('DB::__Init()');
        if (self::$_connected == false) {
            self::$_mysqli = new \mysqli($host, $user, $pass, $db);
            if (self::$_mysqli->connect_error) {
                throw new \Exception(self::$_mysqli->connect_error);
            } else {
                self::$_connected = true;
            }
        }
        _u();
    }

    public static function isConnected(){
        return static::$_connected;
    }
    public static function disconnect(){
        if (static::$_connected || static::$_mysqli !== null) {
            static::$_mysqli->close();
            static::$_mysqli = null;
            static::$_connected = false;
        }
    }

    private static function prepare_query($query)
    {
        $result = null;
        if (is_object($query) && method_exists($query, 'build')) {
            $result = $query->build();
        } else if (is_string($query)) {
            $result = $query;
        } else {
            trigger_error('Not a valid Query', E_USER_WARNING);
            $result = null;
        }
        return $result;
    }

    private static function run_query($query, $purpose, $result_type = NULL)
    {
        $_t = microtime(true);
        self::$_error = false;
        $result = false;
        $queryString = self::prepare_query($query);

        $query_result = self::$_mysqli->query($queryString);
        if (self::$_mysqli->error) {
            trigger_error(self::$_mysqli->error, E_USER_WARNING);
            $result = false;
        } else {
            self::$_insert_id = self::$_mysqli->insert_id;
            self::$_affected_rows = self::$_mysqli->affected_rows;
            if ($purpose == self::OP_QUERY) {
                $result = true;
            } else if ($purpose == self::OP_GET_RESULTS) {
                $result =array();
                if ($query_result->num_rows > 0) {
                    if ($result_type == self::OBJECT) {
                        while ($row = $query_result->fetch_object()) {
                            $result[] = $row;
                        }
                    } else if ($result_type == self::ASSOC) {
                        while ($row = $query_result->fetch_assoc()) {
                            $result[] = $row;
                        }
                    } else if ($result_type == self::NUM) {
                        while ($row = $query_result->fetch_array(MYSQLI_NUM)) {
                            $result[] = $row;
                        }
                    }
                }
            } else if ($purpose == self::OP_GET_ROW) {
                $result =array();
                if ($query_result->num_rows > 0) {
                    if ($result_type == self::OBJECT) {
                        $result = $query_result->fetch_object();
                    } else if ($result_type == self::ASSOC) {
                        $result = $query_result->fetch_assoc();
                    } else if ($result_type == self::NUM) {
                        $result = $query_result->fetch_array(MYSQLI_NUM);
                    }
                }
            } else if ($purpose == self::OP_GET_COL) {
                $result =array();
                if ($query_result->num_rows > 0) {
                    while ($row = $query_result->fetch_array(MYSQLI_NUM)) {
                        $result[] = $row[0];
                    }
                }
            } else if ($purpose == self::OP_GET_VAR) {
                $result = false;
                if ($query_result->num_rows > 0) {
                    $row = $query_result->fetch_array(MYSQLI_NUM);
                    $result = $row[0];
                }
            }
            if (is_object($query_result) && method_exists($query_result, 'free')) {
                $query_result->free();
            }
        }
        _d($query,'DB ('.(round((microtime(true)-$_t)* 100000)/100).'ms) > ');
        return $result;
    }

    public static function query($query)
    {
        _g('DB::query');_d($query);_u();
        $result = self::run_query($query, self::OP_QUERY);
        return $result;
    }

    public static function get($query, $result_type = self::OBJECT)
    {
        $result = self::run_query($query, self::OP_GET_RESULTS, $result_type);
        return $result;
    }

    public static function row($query, $result_type = self::OBJECT)
    {
        $result = self::run_query($query, self::OP_GET_ROW, $result_type);
        return $result;
    }


    public static function col($query)
    {
        $result = self::run_query($query, self::OP_GET_COL);
        return $result;
    }

    public static function val($query)
    {
        $result = self::run_query($query, self::OP_GET_VAR);
        return $result;
    }

    public static function error()
    {
        return self::$_error;
    }

    public static function insert_id()
    {
        return self::$_insert_id;
    }

    public static function affected_rows()
    {
        return self::$_affected_rows;
    }

    public static function escape($data)
    {
        return self::$_mysqli->real_escape_string($data);
    }

    public static function table_exists($tableName)
    {
        $query = 'SHOW TABLES LIKE "' . $tableName . '"';
        $result = self::get($query);
        return (bool)$result;
    }


}