<?php

namespace Pepper\Processes;

use Starlight\Database\MySQL;

/**
 * Performs basic user functions.
 */
class Users {
    /**
     * @var MySQL The database
     */
    private MySQL $db;

    /**
     * Constructs the class (gets everything ready!)
     */
    public function __construct(){
        $this->db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }

    /**
     * Coverts a username into a UUID.
     * @param $username string The username to search for.
     * @return string|bool The UUID or false.
     */
    public function usernameToUuid(string $username): string|bool
    {
        $user = $this->db->fetchOne("SELECT `uuid` FROM users WHERE `username` = ?",[$username]);

        if ($this->db->numRows() > 0) {
            return $user['uuid'];
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
        $user = $this->db->fetchOne("SELECT `username` FROM users WHERE `uuid` = ?",[$uuid]);

        if ($this->db->numRows() > 0) {
            return $user['username'];
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
        $user = $this->db->fetchOne("SELECT `name` FROM users WHERE `uuid` = ?",[$uuid]);

        if ($this->db->numRows() > 0) {
            return $user['name'];
        } else {
            return false;
        }
    }

    /**
     * Gets a list of the user's friends.
     * @param $uuid string The UUID to search for.
     * @return array List of friend's UUIDs.
     */
    public function getFriends(string $uuid): array
    {
        $following = $this->db->fetchAll("SELECT `following` FROM users_following WHERE `user` = ?",[$uuid]);
        $numFollowing = $this->db->numRows();
        $followers = $this->db->fetchAll("SELECT `user` FROM users_following WHERE `following` = ?",[$uuid]);
        $numFollowers = $this->db->numRows();

        if ($numFollowing > 0 && $numFollowers > 0) {
            $followingIds = array_column($following, 'following');
            $followerIds = array_column($followers, 'user');
            return array_intersect($followingIds, $followerIds);
        } else {
            return [];
        }
    }

    /**
     * Checks if a user has preferences set.
     * @param $uuid string The UUID to search for.
     * @return bool true/false.
     */
    public function hasPreferences(string $uuid): bool
    {
        $this->db->fetchAll("SELECT id FROM users_preferences WHERE uuid = ?",[$uuid]);

        return $this->db->numRows() === 1;
    }
}
