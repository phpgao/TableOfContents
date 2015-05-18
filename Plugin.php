<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Table Of Contents 自动文章目录
 *
 * @package TableOfContents
 * @author 老高
 * @version 0.2
 * @link http://www.phpgao.com
 */
class TableOfContents_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('TableOfContents_Plugin', 'replace');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('TableOfContents_Plugin', 'replace');
        Typecho_Plugin::factory('Widget_Archive')->header = array('TableOfContents_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('TableOfContents_Plugin', 'footer');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {

    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}


    public static function header()
    {
        $cssUrl = Helper::options()->siteUrl . "usr/plugins/TableOfContents/css/toc_style.css";
        echo '<link rel="stylesheet" type="text/css" href="' . $cssUrl . '" />';
    }

    public static function footer()
    {

        echo '<script>
                window.content_index_showTocToggle = true;
                function content_index_toggleToc() {
                    var tts = "显示";
                    var tth = "隐藏";
                    if (window.content_index_showTocToggle) {
                        window.content_index_showTocToggle = false;
                        document.getElementById("toc-content").style.display = "none";
                        document.getElementById("content-index-togglelink").innerHTML = tts
                    } else {
                        window.content_index_showTocToggle = true;
                        document.getElementById("toc-content").style.display = "block";
                        document.getElementById("content-index-togglelink").innerHTML = tth
                    }
                }
            </script>';
    }

    /**
     * 插件实现方法
     *
     * @access public
     * @param html $string
     * @return string
     */
    public static function replace($content, $class, $string)
    {

        $html_string = is_null($string) ? $content : $string;

        if( $class->is('index') || $class->is('search') || $class->is('date')){
            return $html_string;
        }

        $toc = self::create_toc_with_dom($html_string);
        return $toc;
    }

    /**
     * copyright http://www.westhost.com/contest/php/function/create-table-of-contents/124
     * @param $content
     * @return array
     */
    public static function create_toc($content)
    {
        preg_match_all( '/<h([2-3])(.*)>([^<]+)<\/h[2-3]>/i', $content, $matches, PREG_SET_ORDER );
        if(count($matches) < 3)
            return array(
            'toc' => '',
            'content' => $content
            );

        $anchors = array();
        $toc = '<div id="toc"><div id="toc-hide"><span class="am-icon-list am-btn-xs am-btn" id="toc-switch" > 显隐</span></div>'."\n";

        $toc .= '<p id="toc-header">' . _t('文章目录') . '</p><ul id="toc-ul">';
        $i = $oder = $prevlvl = 0;
        $anchor_base = 'phpgao';

        foreach ( $matches as $heading ) {

            if ($i == 0)
                $startlvl = $heading[1];


            $lvl = $heading[1];

            $ret = preg_match( '/id=[\'|"](.*)?[\'|"]/i', stripslashes($heading[2]), $anchor );
            // 判断是否需要添加锚点
            if ( $ret && $anchor[1] != '' ) {
                $anchor = stripslashes( $anchor[1] );
                $add_id = false;
            } else {
                $anchor = preg_replace( '/\s+/', '-', preg_replace('/[^a-z\s]/', '', strtolower( $heading[3] ) ) );
                $add_id = true;
                $anchor = $anchor_base . $anchor;
            }



            // 如果相同，则入数组递增输出
            if ( !in_array( $anchor, $anchors ) ) {
                $anchors[] = $anchor;
            } else {
                $orig_anchor = $anchor;
                $i = 2;
                while ( in_array( $anchor, $anchors ) ) {
                    $anchor = $orig_anchor.'-'.$i;
                    $i++;
                }
                $anchors[] = $anchor;
            }

            // 为文章中H元素添加锚点
            if ( $add_id ) {
                if( $lvl == '2' ) {
                    $oder++;
                    $content = substr_replace($content, '<h' . $lvl . ' id="' . $anchor . '"' . $heading[2] . '>' . self::dec2roman($oder) . ' ' . $heading[3] . '</h' . $lvl . '>', strpos($content, $heading[0]), strlen($heading[0]));
                }else{
                    $content = substr_replace($content, '<h' . $lvl . ' id="' . $anchor . '"' . $heading[2] . '>' . $heading[3] . '</h' . $lvl . '>', strpos($content, $heading[0]), strlen($heading[0]));

                }
            }
            //判断是否有title属性
            $ret = preg_match( '/title=[\'|"](.*)?[\'|"]/i', stripslashes( $heading[2] ), $title );
            if ( $ret && $title[1] != '' )
                $title = stripslashes( $title[1] );
            else{
                if( $lvl == '2' ) {
                    $title = self::dec2roman($oder) . $heading[3];
                }else{
                    $title = $heading[3];
                }
            }

            $title = trim( strip_tags( $title ) );

            if ($i > 0) {
                if ($prevlvl < $lvl) {
                    $toc .= "\n"."<ul>"."\n";
                } else if ($prevlvl > $lvl) {
                    $toc .= '</li>'."\n";
                    while ($prevlvl > $lvl) {
                        $toc .= "</ul>"."\n".'</li>'."\n";
                        $prevlvl--;
                    }
                } else {
                    $toc .= '</li>'."\n";
                }
            }

            $j = 0;
            $toc .= '<li><a href="#'.$anchor.'">'.$title.'</a>';
            $prevlvl = $lvl;

            $i++;
        }

        unset( $anchors );

        while ( $lvl > $startlvl ) {
            $toc .= "\n</ol>";
            $lvl--;
        }

        $toc .= '</li>'."\n";
        $toc .= '</ul></div>'."\n";

        return $toc . $content;
    }


    public static function create_toc_with_dom($content)
    {
        require_once 'simple_html_dom.php';

        $html = str_get_html($content);

        $toc = '<div class="toc-index"><div class="toc-title">本文目录</div><span class="toc-toggle">[<a id="content-index-togglelink" href="javascript:content_index_toggleToc()">隐藏</a>]</span><div id="toc-content">';
        $toc .= '';
        $last_level = 0;
        $count_h2 = 0;
        $new = $html->find('h2,h3');

        if(count($new) < 3) return $content;

        foreach($new as $h){



            $innerTEXT = trim($h->innertext);
            $id =  str_replace(' ','_',$innerTEXT);
            $level = intval($h->tag[1]);

            if($level == 2){
                $count_h2++;
                $innerTEXT = self::dec2roman($count_h2) . ' ' . $innerTEXT;
            }
            $h->id= $id; // add id attribute so we can jump to this element

            $h->innertext = $innerTEXT ; // add id attribute so we can jump to this element


            if($level > $last_level)
                // add class
                $toc .= '<ol>';
            else{
                $toc .= str_repeat('</li></ol>', $last_level - $level);
                $toc .= '</li>';
            }
            if($level >= $last_level){
                $toc .= "<li class='toc-level$level'><a href='#{$id}'>{$innerTEXT}</a>";
            }else{
                $toc .= "<li><a href='#{$id}'>{$innerTEXT}</a>";
            }


            $last_level = $level;
        }

        $toc .= str_repeat('</li></ol>', $last_level);
        $toc .= '</div></div>';

        return $toc . "<hr>" . $html->save();

    }


    /**
     * copyright http://www.sharejs.com/codes/php/2433
     * @param $f
     * @return bool|mixed
     */
    public static function dec2roman($f)
    {
        $old_k = '';
        // Return false if either $f is not a real number, $f is bigger than 3999 or $f is lower or equal to 0:
        if(!is_numeric($f) || $f > 3999 || $f <= 0) return false;

        // Define the roman figures:
        $roman = array('M' => 1000, 'D' => 500, 'C' => 100, 'L' => 50, 'X' => 10, 'V' => 5, 'I' => 1);

        // Calculate the needed roman figures:
        foreach($roman as $k => $v) if(($amount[$k] = floor($f / $v)) > 0) $f -= $amount[$k] * $v;

        // Build the string:
        $return = '';
        foreach($amount as $k => $v)
        {
            $return .= $v <= 3 ? str_repeat($k, $v) : $k . $old_k;
            $old_k = $k;
        }

        // Replace some spacial cases and return the string:
        return str_replace(array('VIV','LXL','DCD'), array('IX','XC','CM'), $return . '. ');
    }

}