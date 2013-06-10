<?php
/*
Plugin Name: Kennisnet Theme 
Plugin URI: http://www.kennisnet.nl
Description: Kennisnet UI changes to the Yourls admin interface. 
Version: 1.0
Author: Frank Matheron <frankmatheron@gmail.com>
Author URI: https://github.com/fenuz
*/

// admin form hooks
yourls_add_action('html_head', 'kennisnet_theme_html_head');
function kennisnet_theme_html_head() {
    $plugin_dirname = basename(dirname(__FILE__)); 
    echo 
        '<link rel="stylesheet" href="'.yourls_site_url(false).'/user/plugins/'.$plugin_dirname.'/style.css" type="text/css" media="screen" />';
}
