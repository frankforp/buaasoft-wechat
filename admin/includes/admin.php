<?php
/**
 * This file includes everything needed and defines both public and private Admin Panel API.
 *
 * @author Renfei Song
 * @since 2.0.0
 */

require_once dirname(dirname(dirname(__FILE__))) . '/config.php';

///// Internal API

// Get strings

// User Mgmt.

function current_user() {
    $user = $_COOKIE['user'];
    $token = $_COOKIE['token'];

    global $wxdb; /* @var $wxdb wxdb */
    $sql = $wxdb->prepare("SELECT * FROM `admin_user` WHERE userName = '%s'", $user);
    $user = $wxdb->get_row($sql, ARRAY_A);
    if ($user) {
        if (sha1(LOGIN_SALT . $user['userName']) == $token) {
            return $user;
        }
    }

    return null;
}

function current_user_id() {
    return ($user = current_user()) == null ? 0 : $user['id'];
}

function current_user_name() {
    return ($user = current_user()) == null ? null : $user['userName'];
}

function is_admin() {
    return ($user = current_user()) != null && ($user['userType'] == 1 || $user['userType'] == 0);
}

function is_superadmin() {
    return ($user = current_user()) != null && $user['userType'] == 0;
}

function is_disabled() {
    return ($user = current_user()) != null && $user['userType'] == 2;
}

function is_logged_in() {
    return current_user() != null;
}

function log_in($username, $password, $remember) {
    global $wxdb; /* @var $wxdb wxdb */
    $sql = $wxdb->prepare("SELECT * FROM `admin_user` WHERE userName = '%s' AND hashedPassword = '%s'", $username, sha1($password));
    $user = $wxdb->get_row($sql, ARRAY_A);
    if ($user) {
        if ($remember) {
            setcookie('user', $user['userName'], time() + 3600 * 24 * 30);
            setcookie('token', sha1(LOGIN_SALT . $user['userName']), time() + 3600 * 24 * 30);
        } else {
            setcookie('user', $user['userName']);
            setcookie('token', sha1(LOGIN_SALT . $user['userName']));
        }
        return true;
    }
    return false;
}

function log_out() {
    setcookie('id', null, 0);
    setcookie('token', null, 0);
}

function register($username, $password) {
    global $wxdb; /* @var $wxdb wxdb */
    $success = $wxdb->insert('admin_user', array(
        'userName' => $username,
        'hashedPassword' => sha1($password),
        'userType' => 2
    ));
    return $success != false;
}

// Pages and Items

function has_settings_page(BaseModule $module) {
    return file_exists(ABSPATH . 'modules/' . get_class($module) . '/settings.php');
}

function settings_page_url(BaseModule $module) {
    return ROOT_URL . 'modules/' . get_class($module) . '/settings.php';
}

function include_settings($page_or_module_name) {
    global $global_options;
    if (array_key_exists($page_or_module_name, $global_options))
        require_once ABSPATH . 'admin/includes/global-options-' . $page_or_module_name . '.php';
    else
        require_once ABSPATH . 'modules/' . $page_or_module_name . '/settings.php';
}

function list_global_setting_items() {
    global $global_options;
    global $global_option_icons;
    foreach ($global_options as $slug_name => $display_name) {
        $icon_name = $global_option_icons[$slug_name];
        $class = $_GET['page'] == $slug_name ? 'current' : '';
        $template = '<li class="module-navigation-item %s"><a href="%s"><i class="fa fa-lg fa-fw fa-%s"></i>&nbsp; %s</a></li>';
        echo sprintf($template, $class, ROOT_URL . 'admin/index.php?page=' . $slug_name, $icon_name, $display_name);
    }
}

function list_module_setting_items() {
    global $modules;
    foreach ($modules as $module) {
        if (has_settings_page($module)) {
            /* @var $module BaseModule */
            $class = $_GET['page'] == get_class($module) ? 'current' : '';
            $template = '<li class="module-navigation-item %s"><a href="%s">%s</a></li>';
            echo sprintf($template, $class, ROOT_URL . 'admin/index.php?page=' . get_class($module), $module->display_name());
        }
    }
}

// Misc.

function redirect($location, $status = 302) {
    header("Location: " . $location, true, $status);
}

///// Public Admin Panel API

function submit_button($text = 'Submit', $class = '') {
    $template = '<button type="submit" name="submit" class="button submit-button %s"><i class="fa fa-check"></i> %s</button>';
    echo sprintf($template, $class, $text);
}

function reset_button($callback = '', $text = 'Reset', $class = '') {
    $template = '<button class="button reset-button %s" onclick="%s;return false;">%s</button>';
    echo sprintf($template, $class, $callback, $text);
}