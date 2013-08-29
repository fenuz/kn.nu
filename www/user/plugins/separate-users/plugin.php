<?php
/*
Plugin Name: Separate Users
Plugin URI: http://virgingroupdigital.wordpress.com
Description: Allow some filtering of URLs based on the user that created them
Version: 0.3-kennisnet
Author: Ian Barber <ian.barber@gmail.com>
Author URI: http://phpir.com/
*/

// Define the username given full view of the stats 
if( !defined( 'SEPARATE_USERS_ADMIN_USER' ) ) {
    define( 'SEPARATE_USERS_ADMIN_USER', 'admin' );
}

// Configuration constant to show/hide the user in the keyword table
if( !defined( 'SEPARATE_USERS_SHOW_USER_IN_TABLE' ) ) {
    define( 'SEPARATE_USERS_SHOW_USER_IN_TABLE', TRUE );
}

yourls_add_action( 'insert_link', 'separate_users_insert_link' );
yourls_add_action( 'activated_separate-users/plugin.php', 'separate_users_activated' );
yourls_add_filter( 'admin_list_where', 'separate_users_admin_list_where' );
yourls_add_filter( 'is_valid_user', 'separate_users_is_valid_user' );
yourls_add_filter( 'api_url_stats', 'separate_users_api_url_stats' );

if ( SEPARATE_USERS_SHOW_USER_IN_TABLE ) {
    yourls_add_filter( 'table_head_cells', 'separate_users_alter_table_head_cells' );
    yourls_add_filter( 'table_add_row', 'separate_users_alter_table_row' );
}


/**
 * Activate the plugin, and add the user column to the table if not added
 *
 * @param array $args 
 */
function separate_users_activated($args) {
        global $ydb; 
        
        $table = YOURLS_DB_TABLE_URL;
	$results = $ydb->get_results("DESCRIBE $table");
	$activated = false;
	foreach($results as $r) {
	        if($r->Field == 'user') {
	                $activated = true;
	        }
	}
	if(!$activated) {
		$ydb->query("ALTER TABLE `$table` ADD `user` VARCHAR(255) NULL");
	}
}

/**
 * Filter out URL access which you are not allowed
 *
 * @param array $return 
 * @param string $shorturl 
 * @return array
 */
function separate_users_api_url_stats( $return, $shorturl ) {
        $keyword = str_replace( YOURLS_SITE . '/' , '', $shorturl ); // accept either 'http://ozh.in/abc' or 'abc'
	$keyword = yourls_sanitize_string( $keyword );
        $keyword = addslashes($keyword);
        
        if(separate_users_is_valid($keyword)) {
                return $return;
        } else {
                return array('simple' => "URL is owned by another user", 'message' => 'URL is owned by another user', 'errorCode' => 403);
        }
}


/**
 * Restrict users viewing info pages to just those that have the permission
 *
 * @param bool $is_valid 
 * @return bool is_valid
 */
function separate_users_is_valid_user($is_valid) {
        global $keyword; 
        
        if(!$is_valid || !defined("YOURLS_INFOS")) {
                return $is_valid;
        }

        return separate_users_is_valid($keyword) ? true : "Sorry, that URL was created by another user."; 
}

/**
 * Add the user creating a link to the link when creating
 *
 * @param array $actions 
 */
function separate_users_insert_link($actions) {
        global $ydb; 
        
        $keyword = $actions[2];
        $user = addslashes(YOURLS_USER); // this is pretty noddy, could do with some better filtering/checking
        $table = YOURLS_DB_TABLE_URL;
        
        // Insert $keyword against $username
        $result = $ydb->query("UPDATE `$table` SET  `user` = '" . $user . "' WHERE `keyword` = '" . $keyword . "'");
}

/**
 * Filter out the records which do not belong to the user. 
 *
 * @param string $where 
 * @return string
 */
function separate_users_admin_list_where($where) {
        global $ydb;
        $user = YOURLS_USER; 
        if(separate_users_is_admin_user($user)) {
                return $where; // Allow admin user to see the lot. 
        }
        return $where . " AND (`user` = '" . $ydb->escape($user) . "' OR `user` IS NULL) ";
}

/**
 * Internal module function for testing user access to a keyword
 *
 * @param string $user 
 * @param string $keyword 
 * @return boolean
 */
function separate_users_is_valid( $keyword ) {
        global $ydb; 
        
        $user = YOURLS_USER;
        if(separate_users_is_admin_user($user)) {
                return true;
        }
        $table = YOURLS_DB_TABLE_URL;
        $result = $ydb->query("SELECT 1 FROM `$table` WHERE  (`user` IS NULL OR `user` = '" . $ydb->escape($user) . "') AND `keyword` = '" . $ydb->escape($keyword) . "'");
        return $result > 0;
}

/**
 * Returns true if the user is considered an admin user. Admin users are 
 * allowed to see and edit all keywords. Non admin users are only allowed to 
 * see and edit keywords they created.
 *
 * By default a user with the username 'admin' is considered the administrator. 
 * Other plugin's can add additional administrators by using the 
 * seperate_users_is_admin_user_filter($is_admin, $user).
 *
 * @param string $user the username of an user
 * @return boolean true if the user should be considered an administrator.
 */
function separate_users_is_admin_user($user) {
    $is_admin = $user == SEPARATE_USERS_ADMIN_USER;
    $is_admin = yourls_apply_filter('separate_users_is_admin_user_filter', $is_admin, $user);
    return $is_admin;
}

/** 
 * Returns the user that created the keyword.
 *
 * @param string $keyword
 * @return string the username of the user that created the keyword, or null if 
 *                we do not know who created the keyword.
 */
function separate_users_get_user($keyword) {
    global $ydb;
    $query = "SELECT user FROM " . YOURLS_DB_TABLE_URL . " WHERE `keyword` = '" . $ydb->escape($keyword) . "'";  
    return $ydb->get_var($query);
}

/**
 * Filter that replaces the ip column with a user column 
 */
function separate_users_alter_table_head_cells($cells) {
    $cells['ip'] = 'User';
    return $cells;
}

/**
 * Filter that replaces the ip cell with a user cell. 
 */
function separate_users_alter_table_row($row, $keyword, $url, $title, $ip, $clicks, $timestamp) {
    $user = separate_users_get_user($keyword); 
    $user_cell = "<td>$user</td>"; 
    return preg_replace('/<td id="ip-\w+" class="ip">'.preg_quote($ip, '/').'<\/td>/', $user_cell, $row);
} 
