<?php

/**
 * 微信推送评论通知 - Plus版
 * 
 * @package CommentPlus
 * @author Han
 * @version 1.0.0
 * @link https://www.vvhan.com
 */
class CommentPlus_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {

        Typecho_Plugin::factory('Widget_Feedback')->comment = array('CommentPlus_Plugin', 'sc_send');
        Typecho_Plugin::factory('Widget_Feedback')->trackback = array('CommentPlus_Plugin', 'sc_send');
        Typecho_Plugin::factory('Widget_XmlRpc')->pingback = array('CommentPlus_Plugin', 'sc_send');

        return _t('请配置此插件的 Token, 以使您的推送生效');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
    }

    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $vvhanRadio = new Typecho_Widget_Helper_Form_Element_Radio('vvhanname', array(0 => 'Plus+', 1 => '酷推'), 0, _t('选择 Plus+ 或者 酷推（推荐PlusPlus）'));
        $form->addInput($vvhanRadio);

        $key = new Typecho_Widget_Helper_Form_Element_Text('vvhantoken', NULL, NULL, _t('秘钥'), _t('在秘钥处 Plus+填入Plus+的 Token ，酷推填入酷推的 Skey<br />Plus+注册 <a href="https://pushplus.hxtrip.com/">注册</a><br />酷推注册 <a href="https://cp.xuthus.cc/">注册</a><br />
        注册后需要绑定你的 微信号 或者 QQ号 才能收到推送！'));
        $form->addInput($key->addRule('required', _t('您必须填写一个正确的 Token')));
    }

    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 微信推送
     * 
     * @access public
     * @param array $comment 评论结构
     * @param Typecho_Widget $post 被评论的文章
     * @return void
     */
    public static function sc_send($comment, $post)
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $vvhanToken = $options->plugin('CommentPlus')->vvhantoken;
        $vvhanOr = !$options->plugin('CommentPlus')->vvhanname;
        $text = "有人在您的博客发表了评论";
        if ($vvhanOr && substr($comment['text'], 0, 8) === '{!{data:') {
            preg_match_all('/data:image(.*?)\}!\}/i', $comment['text'], $out);
            $conHtmlorImg = "<img src='" . str_replace('}!}', '', $out[0][0]) . "' style='width: 100%;'>";
        } else {
            $conHtmlorImg = $comment['text'];
        }
        $desp = "<span>文章：</span><a href='{$post->permalink}'
    style='text-decoration: none;color:cornflowerblue ;'>{$post->title}</a><br><span>用户：{$comment['author']}</span><br><span>评论：</span>{$conHtmlorImg}";
        $postdata = http_build_query(
            array(
                'title' => $text,
                'content' => $desp,
                'token' => $vvhanToken
            )
        );
        $opts = array(
            'http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );
        $context  = stream_context_create($opts);
        $result = $vvhanOr ? file_get_contents('http://pushplus.hxtrip.com/send', false, $context) : file_get_contents('https://push.xuthus.cc/send/' . $vvhanToken . '?c=' . $post->permalink . 'ㅤ被评论ㅤ内容：' . $conHtmlorImg);
        return  $comment;
    }
}
