<?php
/*
Plugin Name:Wppao Image
Plugin URI: https://wppao.com/posts/384.html
Description: 提供网站编辑过程中，必须要使用的一些图片功能，如图片远程本地化，图片水印等等。
Author: 缘殊
Version: 1.2.0
Author URI: https://wppao.com/
*/

//内部开发代号wppimg

if(!defined('ABSPATH')){
    return;
}

//DEFINE
define('WPPAO_IMAGE_KEY','wppimg_options');
define('WPPAO_IMAGE_VERSION', '1.2.0' );
define('WPPAO_IMAGE_DIR', plugin_dir_path( __FILE__ ) );
define('WPPAO_IMAGE_URI', plugins_url( '/', __FILE__ ) );
define('WPPAO_IMAGE_HOST', site_url());

// Module
require WPPAO_IMAGE_DIR.'/module/helper.php';
require WPPAO_IMAGE_DIR.'/module/watermark.php';

register_activation_hook( __FILE__, 'wppimg_wm_makedir');



// Options
if(!class_exists('WPPAO_PGS_SETTING')){
    include (WPPAO_IMAGE_DIR . '/options/initialization.php' );
}

$WppaoImage_Plugin = array(
    'slug' => 'wppaoimage',
    'name' => 'WP泡图片增强',
    'plugin_id' => 'wppao_image',
    'ver' => WPPAO_IMAGE_VERSION,
    'title' => 'WP泡图片增强',
    'icon' => WPPAO_IMAGE_URI.'/imgs/plugin_icon.png',
    'position' => 30,
    'key' => WPPAO_IMAGE_KEY,
    'basename' => plugin_basename( __FILE__ ),
    'option' => array('domain' => 'wppao.com',
        'version' => WPPAO_IMAGE_VERSION,
        'option'  => array(
            array("title" => "图片水印设置", "group" => 'wm_group_switch',"type" => "title","first" =>"1","desc" => "每一张经过Wordpress上传的图片都会自动水印,测试地址:<a href='".WPPAO_IMAGE_HOST."/wp-admin/admin.php?page=wppaoimage_helper'>点我打开</a>"),
            array( "title" => "水印功能", "name" => "wm_open", "desc" => "选择是否开启水印功能",  "type" => "toggle", "std" => "false"),
            array( "title" => "水印类型", "group" => 'wm_group_switch',"name" => "wm_type", "desc" => "要什么样的水印呢？",  "type" => "radio", 'options'=>array('text'=>'文字','image'=>'图片'), "std" => "text"),
            array( "title" => "最小水印宽度", "name" => "wm_min_width", "desc" => "以下尺寸不水印，值为像素px",  "type" => "text", "std" => "250"),
            array( "title" => "最小水印高度", "name" => "wm_min_height", "desc" => "以下尺寸不水印，值为像素px",  "type" => "text", "std" => "250"),
            array( "title" => "水印字体", "group" => 'wm_group_text' , "name" => "wm_text_font", "desc" => "你可以上传自定义字体到【 ".WPPAO_IMAGE_HOST."/wp-content/uploads/wppimg/fonts/ 】目录下，默认字体不支持中文，请前往WP泡公众号回复水印字体获取可商用中文字体下载地址，或自行搜索字体使用。",  "type" => "wppimg_fonts", "std" => "Arial"),
            array( "title" => "水印字体大小", "group" => 'wm_group_text' ,"name" => "wm_text_size", "desc" => "值为像素，1-72之间",  "type" => "text", "std" => "72"),
            array( "title" => "水印字体内容", "group" => 'wm_group_text' ,"name" => "wm_text_content", "desc" => "不要过长，不要超过15个字符",  "type" => "text", "std" => "wp泡图片插件水印"),
            array( "title" => "水印字体颜色", "group" => 'wm_group_text' ,"name" => "wm_text_color", "desc" => "颜色，可以按需要选择",  "type" => "color", "std" => "200"),
            array( "title" => "水印位置", "name" => "wm_position", "desc" => "必须选一个位置",  "type" => "wppimg_position", "std" => "200"),
            array( "title" => "水平坐标调整", "name" => "wm_x_just", "desc" => "进行水印水平位置调整，值为像素px",  "type" => "text", "std" => "0"),
            array( "title" => "垂直坐标调整", "name" => "wm_y_just", "desc" => "进行水印垂直位置调整，值为像素px",  "type" => "text", "std" => "0"),
            array( "title" => "水印透明度", "name" => "wm_opacity", "desc" => "水印透明度，值为1-100之间",  "type" => "text", "std" => "100"),
            array( "title" => "图片压缩质量", "group" => 'wm_group_image' , "name" => "wm_jpeg_qa", "desc" => "图片压缩质量，值为1-100之间",  "type" => "text", "std" => "100"),
            array( "title" => "水印图","group" => 'wm_group_image' , "name" => "wm_image_content", "desc" => "如果用图片水印就需要这个了哦",  "type" => "upload", "std" => ""),
        )
    ),
    'submenu' => array(
        array("title"=>"插件调试","slug"=>"_helper","func"=>"wppimg_setting_helper"),
    ),
);

$GLOBALS['WppaoImage_Plugin'] = new WPPAO_PGS_SETTING($WppaoImage_Plugin);

$wppimg_options = get_option(WPPAO_IMAGE_KEY);
$wm_open = ( isset( $wppimg_options['wm_open'] ) && $wppimg_options['wm_open'] ) ? $wppimg_options['wm_open'] : false;
if($wm_open) $wppimg_wm = new WPPAO_Watermark();
