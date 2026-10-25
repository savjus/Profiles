<?php

include("constants.php");

class MySQLDB {

    var $connection;         //The MySQL database connection
    var $num_active_users;   //Number of active users viewing site
    var $num_active_guests;  //Number of active guests viewing site
    var $num_members;        //Number of signed-up users

    /* Note: call getNumMembers() to access $num_members! */

    /* Class constructor */

    function __construct() {
        $this->MySQLDB();
    }

    function MySQLDB() {
        if (function_exists("mysqli_report")) {
            mysqli_report(MYSQLI_REPORT_OFF);
        }
        $this->connection = mysqli_connect(DB_SERVER, DB_USER, DB_PASS);
        if (!$this->connection) {
            die("Nepavyko prisijungti prie MySQL: "
                    . htmlspecialchars(mysqli_connect_error(), ENT_QUOTES, "UTF-8"));
        }
        if (DB_NAME === "" || strpos(DB_NAME, "\0") !== false) {
            die("DB_NAME reikšmė faile include/constants.php yra netinkama.");
        }
        $quotedDatabase = "`" . str_replace("`", "``", DB_NAME) . "`";
        $created = mysqli_query($this->connection, "CREATE DATABASE IF NOT EXISTS " . $quotedDatabase
                . " CHARACTER SET utf8 COLLATE utf8_general_ci");
        $createError = $created ? "" : mysqli_error($this->connection);
        if (!mysqli_select_db($this->connection, DB_NAME)) {
            die("Nepavyko atidaryti duomenų bazės. Patikrinkite DB_NAME ir MySQL teises "
                    . "(CREATE DATABASE naujai DB, CREATE/ALTER lentelėms): "
                    . htmlspecialchars($createError . " " . mysqli_error($this->connection), ENT_QUOTES, "UTF-8"));
        }
        if (!mysqli_set_charset($this->connection, "utf8")) {
            die("Nepavyko nustatyti MySQL UTF-8 koduotės: "
                    . htmlspecialchars(mysqli_error($this->connection), ENT_QUOTES, "UTF-8"));
        }

        $this->initializeSchema();

        /**
         * Only query database to find out number of members
         * when getNumMembers() is called for the first time,
         * until then, default value set.
         */
        $this->num_members = -1;

        if (TRACK_VISITORS) {
            /* Calculate number of users at site */
            $this->calcNumActiveUsers();

            /* Calculate number of guests at site */
            $this->calcNumActiveGuests();
        }
    }

    function initializeSchema() {
        if (!mysqli_query($this->connection, "CREATE TABLE IF NOT EXISTS `app_migrations` (
                `version` varchar(64) NOT NULL,
                `applied_at` int(10) unsigned NOT NULL,
                PRIMARY KEY (`version`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8")) {
            die("Nepavyko paruošti migracijų žurnalo: "
                    . htmlspecialchars(mysqli_error($this->connection), ENT_QUOTES, "UTF-8"));
        }
        $versionResult = mysqli_query($this->connection,
                "SELECT version FROM `app_migrations` WHERE version = 'profiles_social_v1'");
        if (!$versionResult) {
            die("Nepavyko patikrinti migracijos būsenos: "
                    . htmlspecialchars(mysqli_error($this->connection), ENT_QUOTES, "UTF-8"));
        }
        $alreadyMigrated = mysqli_num_rows($versionResult) > 0;
        mysqli_free_result($versionResult);

        $expectedTables = array(
            TBL_USERS,
            TBL_ACTIVE_USERS,
            TBL_ACTIVE_GUESTS,
            TBL_BANNED_USERS,
            "social_posts",
            "social_comments",
            "social_reactions",
            "social_follows",
            "social_messages"
        );
        $tableResult = mysqli_query($this->connection, "SHOW TABLES");
        if (!$tableResult) {
            die("Nepavyko patikrinti duomenų bazės lentelių: "
                    . htmlspecialchars(mysqli_error($this->connection), ENT_QUOTES, "UTF-8"));
        }
        $existingTables = array();
        while ($table = mysqli_fetch_row($tableResult)) {
            $existingTables[$table[0]] = true;
        }
        mysqli_free_result($tableResult);

        $schemaComplete = true;
        foreach ($expectedTables as $tableName) {
            if (!isset($existingTables[$tableName])) {
                $schemaComplete = false;
                break;
            }
        }
        if ($schemaComplete) {
            $columnResult = mysqli_query($this->connection, "SHOW COLUMNS FROM `" . TBL_USERS . "`");
            if (!$columnResult) {
                die("Nepavyko patikrinti vartotojų lentelės: "
                        . htmlspecialchars(mysqli_error($this->connection), ENT_QUOTES, "UTF-8"));
            }
            $existingColumns = array();
            while ($column = mysqli_fetch_assoc($columnResult)) {
                $existingColumns[$column['Field']] = true;
            }
            mysqli_free_result($columnResult);
            foreach (array("first_name", "last_name", "bio") as $columnName) {
                if (!isset($existingColumns[$columnName])) {
                    $schemaComplete = false;
                    break;
                }
            }
        }
        if ($alreadyMigrated && $schemaComplete) {
            return;
        }

        $seedDemoUsers = !isset($existingTables[TBL_USERS]);
        $tables = array(
            "CREATE TABLE IF NOT EXISTS `" . TBL_ACTIVE_GUESTS . "` (
                `ip` varchar(45) NOT NULL,
                `timestamp` int(11) unsigned NOT NULL,
                PRIMARY KEY (`ip`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `" . TBL_ACTIVE_USERS . "` (
                `username` varchar(30) NOT NULL,
                `timestamp` int(11) unsigned NOT NULL,
                PRIMARY KEY (`username`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `" . TBL_BANNED_USERS . "` (
                `username` varchar(30) NOT NULL,
                `timestamp` int(11) unsigned NOT NULL,
                PRIMARY KEY (`username`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `" . TBL_USERS . "` (
                `username` varchar(30) NOT NULL,
                `password` varchar(32) DEFAULT NULL,
                `userid` varchar(32) DEFAULT NULL,
                `userlevel` tinyint(1) unsigned NOT NULL,
                `email` varchar(50) DEFAULT NULL,
                `timestamp` int(11) unsigned NOT NULL,
                `first_name` varchar(80) NOT NULL DEFAULT '',
                `last_name` varchar(80) NOT NULL DEFAULT '',
                `bio` varchar(1000) NOT NULL DEFAULT '',
                PRIMARY KEY (`username`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `social_posts` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `username` varchar(30) NOT NULL,
                `content` text NOT NULL,
                `created_at` int(10) unsigned NOT NULL,
                PRIMARY KEY (`id`),
                KEY `social_posts_created` (`created_at`),
                KEY `social_posts_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `social_comments` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `post_id` int(10) unsigned NOT NULL,
                `username` varchar(30) NOT NULL,
                `content` text NOT NULL,
                `created_at` int(10) unsigned NOT NULL,
                PRIMARY KEY (`id`),
                KEY `social_comments_post` (`post_id`),
                KEY `social_comments_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `social_reactions` (
                `post_id` int(10) unsigned NOT NULL,
                `username` varchar(30) NOT NULL,
                `reaction` varchar(20) NOT NULL,
                `created_at` int(10) unsigned NOT NULL,
                PRIMARY KEY (`post_id`, `username`),
                KEY `social_reactions_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `social_follows` (
                `follower` varchar(30) NOT NULL,
                `followed` varchar(30) NOT NULL,
                `created_at` int(10) unsigned NOT NULL,
                PRIMARY KEY (`follower`, `followed`),
                KEY `social_follows_followed` (`followed`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `social_messages` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `sender` varchar(30) NOT NULL,
                `recipient` varchar(30) NOT NULL,
                `content` text NOT NULL,
                `created_at` int(10) unsigned NOT NULL,
                PRIMARY KEY (`id`),
                KEY `social_messages_sender` (`sender`),
                KEY `social_messages_recipient` (`recipient`),
                KEY `social_messages_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        foreach ($tables as $query) {
            if (!mysqli_query($this->connection, $query)) {
                die("Nepavyko sukurti duomenų bazės lentelių: "
                        . htmlspecialchars(mysqli_error($this->connection), ENT_QUOTES, "UTF-8"));
            }
        }

        $profileColumns = array(
            "first_name" => "varchar(80) NOT NULL DEFAULT ''",
            "last_name" => "varchar(80) NOT NULL DEFAULT ''",
            "bio" => "varchar(1000) NOT NULL DEFAULT ''"
        );
        foreach ($profileColumns as $column => $definition) {
            $result = mysqli_query($this->connection,
                    "SHOW COLUMNS FROM `" . TBL_USERS . "` LIKE '" . $column . "'");
            if (!$result) {
                die("Nepavyko patikrinti vartotojų lentelės: "
                        . htmlspecialchars(mysqli_error($this->connection), ENT_QUOTES, "UTF-8"));
            }
            $exists = mysqli_num_rows($result) > 0;
            mysqli_free_result($result);
            if (!$exists && !mysqli_query($this->connection,
                    "ALTER TABLE `" . TBL_USERS . "` ADD COLUMN `" . $column . "` " . $definition)) {
                $error = mysqli_error($this->connection);
                $check = mysqli_query($this->connection,
                        "SHOW COLUMNS FROM `" . TBL_USERS . "` LIKE '" . $column . "'");
                $addedByConcurrentRequest = $check && mysqli_num_rows($check) > 0;
                if ($check) {
                    mysqli_free_result($check);
                }
                if (!$addedByConcurrentRequest) {
                    die("Nepavyko atnaujinti vartotojų lentelės: "
                            . htmlspecialchars($error, ENT_QUOTES, "UTF-8"));
                }
            }
        }

        if (!$seedDemoUsers) {
            $userCountResult = mysqli_query($this->connection, "SELECT COUNT(*) FROM `" . TBL_USERS . "`");
            if (!$userCountResult) {
                die("Nepavyko patikrinti demo vartotojų: "
                        . htmlspecialchars(mysqli_error($this->connection), ENT_QUOTES, "UTF-8"));
            }
            $userCount = mysqli_fetch_row($userCountResult);
            mysqli_free_result($userCountResult);
            $seedDemoUsers = (int) $userCount[0] === 0;
        }

        if ($seedDemoUsers) {
            $demoUsers = array(
                array("Valdytojas", "7ed2b87b255a0348b61226bd7c2ed5b4", MANAGER_LEVEL, 1330553708),
                array("Administratorius", "a2fe399900de341c39c632244eaf8483", ADMIN_LEVEL, 1330553956),
                array("Vartotojas", "9a47f4552955b91bcd8850d73b00e703", USER_LEVEL, 1330553730)
            );
            $statement = mysqli_prepare($this->connection,
                    "INSERT IGNORE INTO `" . TBL_USERS . "` "
                    . "(username, password, userid, userlevel, email, timestamp) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$statement) {
                die("Nepavyko paruošti pradinių demo duomenų: "
                        . htmlspecialchars(mysqli_error($this->connection), ENT_QUOTES, "UTF-8"));
            }
            $password = md5("demo");
            $email = "demo@ktu.lt";
            foreach ($demoUsers as $user) {
                $username = $user[0];
                $userid = $user[1];
                $userlevel = $user[2];
                $timestamp = $user[3];
                if (!mysqli_stmt_bind_param($statement, "sssisi",
                        $username, $password, $userid, $userlevel, $email, $timestamp) ||
                        !mysqli_stmt_execute($statement)) {
                    die("Nepavyko sukurti pradinių demo vartotojų: "
                            . htmlspecialchars(mysqli_stmt_error($statement), ENT_QUOTES, "UTF-8"));
                }
            }
            mysqli_stmt_close($statement);
        }

        $migration = mysqli_prepare($this->connection,
                "INSERT IGNORE INTO `app_migrations` (version, applied_at) VALUES ('profiles_social_v1', ?)");
        if (!$migration) {
            die("Nepavyko užbaigti duomenų bazės migracijos: "
                    . htmlspecialchars(mysqli_error($this->connection), ENT_QUOTES, "UTF-8"));
        }
        $appliedAt = time();
        if (!mysqli_stmt_bind_param($migration, "i", $appliedAt) ||
                !mysqli_stmt_execute($migration)) {
            die("Nepavyko užregistruoti migracijos: "
                    . htmlspecialchars(mysqli_stmt_error($migration), ENT_QUOTES, "UTF-8"));
        }
        mysqli_stmt_close($migration);
    }

    /**
     * confirmUserPass - Checks whether or not the given
     * username is in the database, if so it checks if the
     * given password is the same password in the database
     * for that user. If the user doesn't exist or if the
     * passwords don't match up, it returns an error code
     * (1 or 2). On success it returns 0.
     */
    function confirmUserPass($username, $password) {
        /* Add slashes if necessary (for query) */
        $username = mysqli_real_escape_string($this->connection, $username);

        /* Verify that user is in database */
        $q = "SELECT password FROM " . TBL_USERS . " WHERE username = '$username'";
        $result = mysqli_query($this->connection, $q);
        if (!$result || (mysqli_num_rows($result) < 1)) {
            return 1; //Indicates username failure
        }

        /* Retrieve password from result, strip slashes */
        $dbarray = mysqli_fetch_array($result);
        $dbarray['password'] = stripslashes($dbarray['password']);
        $password = stripslashes($password);

        /* Validate that password is correct */
        if ($password === $dbarray['password']) {
            return 0; //Success! Username and password confirmed
        } else {
            return 2; //Indicates password failure
        }
    }

    /**
     * confirmUserID - Checks whether or not the given
     * username is in the database, if so it checks if the
     * given userid is the same userid in the database
     * for that user. If the user doesn't exist or if the
     * userids don't match up, it returns an error code
     * (1 or 2). On success it returns 0.
     */
    function confirmUserID($username, $userid) {
        /* Add slashes if necessary (for query) */
        $username = mysqli_real_escape_string($this->connection, $username);

        /* Verify that user is in database */
        $q = "SELECT userid FROM " . TBL_USERS . " WHERE username = '$username'";
        $result = mysqli_query($this->connection, $q);
        if (!$result || (mysqli_num_rows($result) < 1)) {
            return 1; //Indicates username failure
        }

        /* Retrieve userid from result, strip slashes */
        $dbarray = mysqli_fetch_array($result);
        $dbarray['userid'] = stripslashes($dbarray['userid']);
        $userid = stripslashes($userid);

        /* Validate that userid is correct */
        if ($userid == $dbarray['userid']) {
            return 0; //Success! Username and userid confirmed
        } else {
            return 2; //Indicates userid invalid
        }
    }

    /**
     * usernameTaken - Returns true if the username has
     * been taken by another user, false otherwise.
     */
    function usernameTaken($username) {
        $username = mysqli_real_escape_string($this->connection, $username);
        $q = "SELECT username FROM " . TBL_USERS . " WHERE username = '$username'";
        $result = mysqli_query($this->connection, $q);
        return (mysqli_num_rows($result) > 0);
    }

    /**
     * usernameBanned - Returns true if the username has
     * been banned by the administrator.
     */
    function usernameBanned($username) {
        $username = mysqli_real_escape_string($this->connection, $username);
        $q = "SELECT username FROM " . TBL_BANNED_USERS . " WHERE username = '$username'";
        $result = mysqli_query($this->connection, $q);
        return (mysqli_num_rows($result) > 0);
    }

    /**
     * addNewUser - Inserts the given (username, password, email)
     * info into the database. Appropriate user level is set.
     * Returns true on success, false otherwise.
     */
    function addNewUser($username, $password, $email) {
        $time = time();
        /* If admin sign up, give admin user level */
        if (strcasecmp($username, ADMIN_NAME) == 0) {
            $ulevel = ADMIN_LEVEL;
        } else {
            $ulevel = USER_LEVEL;
        }
        $q = "INSERT INTO " . TBL_USERS . " (username,password,userid,userlevel,email,timestamp) "
                . "VALUES ('$username', '$password', '0', $ulevel, '$email', $time)";
        return mysqli_query($this->connection, $q);
    }

    /**
     * updateUserField - Updates a field, specified by the field
     * parameter, in the user's row of the database.
     */
    function updateUserField($username, $field, $value) {
        $q = "UPDATE " . TBL_USERS . " SET " . $field . " = '$value' WHERE username = '$username'";
        return mysqli_query($this->connection, $q);
    }

    /**
     * getUserInfo - Returns the result array from a mysql
     * query asking for all information stored regarding
     * the given username. If query fails, NULL is returned.
     */
    function getUserInfo($username) {
        $q = "SELECT * FROM " . TBL_USERS . " WHERE username = '$username'";
        $result = mysqli_query($this->connection, $q);
        /* Error occurred, return given name by default */
        if (!$result || (mysqli_num_rows($result) < 1)) {
            return NULL;
        }
        /* Return result array */
        $dbarray = mysqli_fetch_array($result);
        return $dbarray;
    }

    /**
     * getNumMembers - Returns the number of signed-up users
     * of the website, banned members not included. The first
     * time the function is called on page load, the database
     * is queried, on subsequent calls, the stored result
     * is returned. This is to improve efficiency, effectively
     * not querying the database when no call is made.
     */
    function getNumMembers() {
        if ($this->num_members < 0) {
            $q = "SELECT * FROM " . TBL_USERS;
            $result = mysqli_query($this->connection, $q);
            $this->num_members = mysqli_num_rows($result);
        }
        return $this->num_members;
    }

    /**
     * calcNumActiveUsers - Finds out how many active users
     * are viewing site and sets class variable accordingly.
     */
    function calcNumActiveUsers() {
        /* Calculate number of users at site */
        $q = "SELECT * FROM " . TBL_ACTIVE_USERS;
        $result = mysqli_query($this->connection, $q);
        $this->num_active_users = mysqli_num_rows($result);
    }

    /**
     * calcNumActiveGuests - Finds out how many active guests
     * are viewing site and sets class variable accordingly.
     */
    function calcNumActiveGuests() {
        /* Calculate number of guests at site */
        $q = "SELECT * FROM " . TBL_ACTIVE_GUESTS;
        $result = mysqli_query($this->connection, $q);
        $this->num_active_guests = mysqli_num_rows($result);
    }

    /**
     * addActiveUser - Updates username's last active timestamp
     * in the database, and also adds him to the table of
     * active users, or updates timestamp if already there.
     */
    function addActiveUser($username, $time) {
        $q = "UPDATE " . TBL_USERS . " SET timestamp = '$time' WHERE username = '$username'";
        mysqli_query($this->connection, $q);

        if (!TRACK_VISITORS)
            return;
        $q = "REPLACE INTO " . TBL_ACTIVE_USERS . " VALUES ('$username', '$time')";
        mysqli_query($this->connection, $q);
        $this->calcNumActiveUsers();
    }

    /* addActiveGuest - Adds guest to active guests table */

    function addActiveGuest($ip, $time) {
        if (!TRACK_VISITORS)
            return;
        $q = "REPLACE INTO " . TBL_ACTIVE_GUESTS . " VALUES ('$ip', '$time')";
        mysqli_query($this->connection, $q);
        $this->calcNumActiveGuests();
    }

    /* These functions are self explanatory, no need for comments */

    /* removeActiveUser */

    function removeActiveUser($username) {
        if (!TRACK_VISITORS)
            return;
        $q = "DELETE FROM " . TBL_ACTIVE_USERS . " WHERE username = '$username'";
        mysqli_query($this->connection, $q);
        $this->calcNumActiveUsers();
    }

    /* removeActiveGuest */

    function removeActiveGuest($ip) {
        if (!TRACK_VISITORS)
            return;
        $q = "DELETE FROM " . TBL_ACTIVE_GUESTS . " WHERE ip = '$ip'";
        mysqli_query($this->connection, $q);
        $this->calcNumActiveGuests();
    }

    /* removeInactiveUsers */

    function removeInactiveUsers() {
        if (!TRACK_VISITORS)
            return;
        $timeout = time() - USER_TIMEOUT * 60;
        $q = "DELETE FROM " . TBL_ACTIVE_USERS . " WHERE timestamp < $timeout";
        mysqli_query($this->connection, $q);
        $this->calcNumActiveUsers();
    }

    /* removeInactiveGuests */

    function removeInactiveGuests() {
        if (!TRACK_VISITORS)
            return;
        $timeout = time() - GUEST_TIMEOUT * 60;
        $q = "DELETE FROM " . TBL_ACTIVE_GUESTS . " WHERE timestamp < $timeout";
        mysqli_query($this->connection, $q);
        $this->calcNumActiveGuests();
    }

    /**
     * query - Performs the given query on the database and
     * returns the result, which may be false, true or a
     * resource identifier.
     */
    function query($query) {
        return mysqli_query($this->connection, $query);
    }

}

/* Create database connection */
$database = new MySQLDB;
