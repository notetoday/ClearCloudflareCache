<?php
/**
 * 发布文章或有新评论时将自动清理Cloudflare缓存。
 * 
 * @package 自动清理Cloudflare缓存
 * @author Chris
 * @version 1.0.4
 * @dependence 9.9.2-*
 * @link https://www.notetoday.net
 *
 *
 * version 1.0.0 at 2024-06-04
 * 自动清理Cloudflare缓存
 * 发布文章或有新评论时将自动清理Cloudflare缓存
 *
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class ClearCloudflareCache_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件
     * @return string
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array('ClearCloudflareCache_Plugin', 'clearCache');
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->finishPublish = array('ClearCloudflareCache_Plugin', 'clearCache');
        Typecho_Plugin::factory('Widget_Feedback')->finishComment = array('ClearCloudflareCache_Plugin', 'clearCacheForComment');
        return _t('插件已激活，发布文章或评论后将自动清理Cloudflare缓存。');
    }

    /**
     * 禁用插件
     * @return string
     */
    public static function deactivate()
    {
        return _t('插件已禁用。');
    }

    /**
     * 插件配置面板
     * @param Typecho_Widget_Helper_Form $form
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $email = new Typecho_Widget_Helper_Form_Element_Text('email', NULL, '', _t('Cloudflare账户邮箱'));
        $form->addInput($email);
        $apiKey = new Typecho_Widget_Helper_Form_Element_Text('apiKey', NULL, '', _t('Cloudflare API Key'));
        $form->addInput($apiKey);
        $zoneId = new Typecho_Widget_Helper_Form_Element_Text('zoneId', NULL, '', _t('Cloudflare Zone ID'));
        $form->addInput($zoneId);

        $clearMode = new Typecho_Widget_Helper_Form_Element_Select('clearMode', array(
            'entire' => '清除整站缓存',
            'current' => '仅清除当前URL缓存'
        ), 'current', _t('评论时的清理模式'), _t('选择评论发布时清理缓存的模式'));
        $form->addInput($clearMode);
    }

    /**
     * 个人用户配置面板
     * @param Typecho_Widget_Helper_Form $form
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 清理缓存的方法
     * @param $contents
     * @param $class
     * @throws Typecho_Plugin_Exception
     */
    public static function clearCache($contents, $class)
    {
        self::purgeCache();
    }

    /**
     * 清理缓存的方法，仅用于评论
     * @param $comment
     * @throws Typecho_Plugin_Exception
     */
    public static function clearCacheForComment($comment)
    {
        self::purgeCache();
    }

    /**
     * 执行清理缓存的实际方法
     * @throws Typecho_Plugin_Exception
     */
    private static function purgeCache()
    {
        $options = Helper::options();
        $email = $options->plugin('ClearCloudflareCache')->email;
        $apiKey = $options->plugin('ClearCloudflareCache')->apiKey;
        $zoneId = $options->plugin('ClearCloudflareCache')->zoneId;

        if (!$email || !$apiKey || !$zoneId) {
            return;
        }

        $url = 'https://api.cloudflare.com/client/v4/zones/' . $zoneId . '/purge_cache';
        $headers = array(
            'X-Auth-Email: ' . $email,
            'X-Auth-Key: ' . $apiKey,
            'Content-Type: application/json'
        );
        $data = json_encode(array('purge_everything' => true));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $result = curl_exec($ch);
        curl_close($ch);

        if ($result === false) {
            throw new Typecho_Plugin_Exception(_t('清理Cloudflare缓存失败。'));
        }
    }

}
