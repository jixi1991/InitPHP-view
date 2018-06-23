<?php
/*********************************************************************************
 * InitPHP 3.8.2 国产PHP开发框架   View-view 模板核心文件类
 *-------------------------------------------------------------------------------
 * 版权所有: CopyRight By initphp.com
 * 您可以自由使用该源码，但是在使用过程中，请保留作者信息。尊重他人劳动成果就是尊重自己
 *-------------------------------------------------------------------------------
 * Author:zhuli Dtime:2014-11-25
 ***********************************************************************************/

namespace InitPHP\View;

use InitPHP\View\Template as Template;

class View extends Template {

    public  $view = array(); //视图变量
    private $template_arr = array(); //视图存放器
    private $remove_tpl_arr = array(); //待移除

    /**
     * 模板-设置模板变量-将PHP中是变量输出到模板上，都需要经过此函数
     * 1. 模板赋值核心函数，$key => $value , 模板变量名称 => 控制器中变量
     * 2. 也可以直接用$this->view->view['key'] = $value
     * 3. 模板中对应$key
     * Controller中使用方法：$this->view->assign($key, $value);
     * @param string  $key   KEY值-模板中的变量名称
     * @param array $value value值
     * */
    public function assign($key, $value) {
        $this->view[$key] = $value;
    }

    /**
     * 模板-设置模板 设置HTML模板
     * 1. 设置模板，模板名称和类型，类型选择F和L的时候，是最先显示和最后显示的模板
     * 2. 比如设置 user目录下的userinfo.htm目录，则 set_tpl('user/userinfo') 不需要填写.htm
     * Controller中使用方法：$this->view->set_tpl($template_name, $type = '');
     * @param  string  $template_name 模板名称
     * @param  string  $type 类型，F-头模板，L-脚步模板
     * */
    public function set_tpl($template_name, $type = '') {
        if ($type == 'F') {
            $this->template_arr['F'] = $template_name;
        } elseif ($type == 'L') {
            $this->template_arr['L'] = $template_name;
        } else {
            $this->template_arr[] = $template_name;
        }
    }

    /**
     * 模板-移除模板
     * 1. 如果在控制器的基类中已经导入头部和脚步模板，应用中需要替换头部模板
     * 2. 移除模板需要在display() 模板显示前使用
     * Controller中使用方法：$this->view->remove_tpl($remove_tpl);
     * @param string $remove_tpl 需要移除模板名称
     * */
    public function remove_tpl($remove_tpl) {
        $this->remove_tpl_arr[] = $remove_tpl;
    }

    /**
     * 模板-获取模板数组
     * Controller中使用方法：$this->view->get_tpl();
     * @return array
     * */
    public function get_tpl() {
        return $this->template_arr;
    }

    /**
     * 模板-显示视图
     * 1. 在Controller中需要显示模板，就必须调用该函数
     * 2. 模板解析可以设置 $InitPHP_conf['isviewfilter'] 值,对变量进行过滤
     * Controller中使用方法：$this->view->display();
     * @param string $template
     * */
    public function display( string $template = '') {
        if ($template != '') {
            $this->set_tpl($template);
        }

        $InitPHP_conf = [];
        if (is_array($this->view)) {
            if (isset($InitPHP_conf['isviewfilter'])) {
                $this->view = $this->out_put($this->view);
            }

            foreach ($this->view as $key => $val) {
                $$key = $val;
            }
        }
        $this->template_arr = $this->parse_template_arr($this->template_arr); //模板设置
        foreach ($this->template_arr as $file_name) {
            if (in_array($file_name, $this->remove_tpl_arr)) continue;
            $complie_file_name = $this->template_run($file_name); //模板编译
            if (!file_exists($complie_file_name)) InitPHP::initError($complie_file_name. ' is not exist!');
            include_once($complie_file_name);
        }
    }

    /**
     * 模板-处理视图存放器数组，分离头模板和脚模板顺序
     * @param  array  $arr 视图存放器数组
     * @return array
     * */
    private function parse_template_arr(array $arr) {
        $temp = $arr;
        unset($temp['F'], $temp['L']);
        if (isset($this->template_arr['F'])) { // 头模板
            array_unshift($temp, $this->template_arr['F']);
        }

        if (isset($this->template_arr['L'])) {
            array_push($temp, $this->template_arr['L']);
        }
        return $temp;
    }

    /**
     * 模板-模板变量输出过滤
     * @param  array $value 视图存放器数组
     * @return array
     * */
    private function out_put($value) {
        $value = (array) $value;
        foreach ($value as $key => $val) {
            if (is_array($val)) {
                $value[$key] = self::out_put($value[$key]);
            } elseif (is_object($val)) {
                $value[$key] = $val;
            } else {
                if (function_exists('htmlspecialchars')) {
                    $value[$key] =  htmlspecialchars($val);
                } else {
                    $value[$key] =  str_replace(array("&", '"', "'", "<", ">", "%3C", "%3E"), array("&amp;", "&quot;", "&#039;", "&lt;", "&gt;", "&lt;", "&gt;"), $val);
                }
            }
        }
        return $value;
    }
}
