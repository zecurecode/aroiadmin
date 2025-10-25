<?php
/**
 * Database Connection Handler
 *
 * @package Multiside_Aroi_Integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database class for Aroi admin database connection
 */
class Multiside_Aroi_Database {

    /**
     * Database connection
     */
    private static $connection = null;

    /**
     * Get database connection
     *
     * @return mysqli|false Database connection or false on failure
     */
    public static function get_connection() {
        if (self::$connection === null) {
            self::$connection = @mysqli_connect(
                MULTISIDE_AROI_DB_HOST,
                MULTISIDE_AROI_DB_USER,
                MULTISIDE_AROI_DB_PASS,
                MULTISIDE_AROI_DB_NAME
            );

            if (!self::$connection) {
                error_log('MultiSide Aroi: Database connection failed - ' . mysqli_connect_error());
                return false;
            }

            // Set charset
            mysqli_set_charset(self::$connection, 'utf8mb4');
        }

        return self::$connection;
    }

    /**
     * Execute a query
     *
     * @param string $sql SQL query
     * @return mysqli_result|bool Query result
     */
    public static function query($sql) {
        $conn = self::get_connection();
        if (!$conn) {
            return false;
        }

        $result = mysqli_query($conn, $sql);

        if (!$result) {
            error_log('MultiSide Aroi: Query failed - ' . mysqli_error($conn) . ' | SQL: ' . $sql);
        }

        return $result;
    }

    /**
     * Prepare and execute a statement
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @param string $types Parameter types (e.g., 'ssi' for string, string, int)
     * @return mysqli_result|bool
     */
    public static function prepare_execute($sql, $params = array(), $types = '') {
        $conn = self::get_connection();
        if (!$conn) {
            return false;
        }

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log('MultiSide Aroi: Prepare failed - ' . mysqli_error($conn));
            return false;
        }

        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        if (!mysqli_stmt_execute($stmt)) {
            error_log('MultiSide Aroi: Execute failed - ' . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }

        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);

        return $result;
    }

    /**
     * Insert a record
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int|bool Insert ID or false on failure
     */
    public static function insert($table, $data) {
        $conn = self::get_connection();
        if (!$conn) {
            return false;
        }

        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            $placeholders
        );

        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log('MultiSide Aroi: Insert prepare failed - ' . mysqli_error($conn));
            return false;
        }

        mysqli_stmt_bind_param($stmt, $types, ...$values);

        if (!mysqli_stmt_execute($stmt)) {
            error_log('MultiSide Aroi: Insert execute failed - ' . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }

        $insert_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        return $insert_id;
    }

    /**
     * Update a record
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value to update
     * @param array $where Associative array of column => value for WHERE clause
     * @return bool Success
     */
    public static function update($table, $data, $where) {
        $conn = self::get_connection();
        if (!$conn) {
            return false;
        }

        $set_parts = array();
        foreach (array_keys($data) as $column) {
            $set_parts[] = "$column = ?";
        }

        $where_parts = array();
        foreach (array_keys($where) as $column) {
            $where_parts[] = "$column = ?";
        }

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $set_parts),
            implode(' AND ', $where_parts)
        );

        $values = array_merge(array_values($data), array_values($where));
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log('MultiSide Aroi: Update prepare failed - ' . mysqli_error($conn));
            return false;
        }

        mysqli_stmt_bind_param($stmt, $types, ...$values);

        if (!mysqli_stmt_execute($stmt)) {
            error_log('MultiSide Aroi: Update execute failed - ' . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }

        mysqli_stmt_close($stmt);
        return true;
    }

    /**
     * Escape string for SQL
     *
     * @param string $string String to escape
     * @return string Escaped string
     */
    public static function escape($string) {
        $conn = self::get_connection();
        if (!$conn) {
            return addslashes($string);
        }
        return mysqli_real_escape_string($conn, $string);
    }

    /**
     * Close database connection
     */
    public static function close() {
        if (self::$connection !== null) {
            mysqli_close(self::$connection);
            self::$connection = null;
        }
    }
}
