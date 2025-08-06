<?php

namespace Pepper\Helpers;

use Starlight\Database\SQL;

/**
 * Performs basic user functions.
 */
class Users {
    /**
     * @var SQL The database
     */
    private SQL $db;

    /**
     * Constructs the class (gets everything ready!)
     */
    public function __construct(){
        $this->db = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }

    /**
     * Coverts a username into a UUID.
     * @param $username string The username to search for.
     * @return string|bool The UUID or false.
     */
    public function usernameToUuid(string $username): string|bool
    {
        $query = $this->db->query("SELECT `uuid` FROM `users` WHERE `username` = '".$this->db->escape($username)."'");

        if ($query && $query->num_rows > 0) {
            return $query->fetch_object()->uuid;
        } else {
            return false;
        }
    }

    /**
     * Coverts a UUID into a username.
     * @param $uuid string The UUID to search for.
     * @return string|bool The username or false.
     */
    public function uuidToUsername(string $uuid): string|bool
    {
        $query = $this->db->query("SELECT `username` FROM `users` WHERE `uuid` = '".$this->db->escape($uuid)."'");

        if ($query && $query->num_rows > 0) {
            return $query->fetch_object()->username;
        } else {
            return false;
        }
    }

    /**
     * Coverts a UUID into a name.
     * @param $uuid string The UUID to search for.
     * @return string|bool The name or false.
     */
    public function uuidToName(string $uuid): string|bool
    {
        $query = $this->db->query("SELECT `name` FROM `users` WHERE `uuid` = '".$this->db->escape($uuid)."'");

        if ($query && $query->num_rows > 0) {
            return $query->fetch_object()->name;
        } else {
            return false;
        }
    }
}
