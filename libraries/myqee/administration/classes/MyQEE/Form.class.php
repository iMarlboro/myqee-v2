<?php

/**
 * 表单核心类
 *
 * @author     jonwang(jonwang@myqee.com)
 * @category   MyQEE
 * @package    System
 * @subpackage Core
 * @copyright  Copyright (c) 2008-2012 myqee.com
 * @license    http://www.myqee.com/license.html
 */
class MyQEE_Form
{

    /**
     * Generates an opening HTML form tag.
     *
     * // Form will submit back to the current page using POST
     * echo Form::open();
     *
     * // Form will submit to 'search' using GET
     * echo Form::open('search', array('method' => 'get'));
     *
     * // When "file" inputs are present, you must include the "enctype"
     * echo Form::open(null, array('enctype' => 'multipart/form-data'));
     *
     * @param   string  form action, defaults to the current request URI
     * @param   array   html attributes
     * @return  string
     * @uses	HttpIO::instance
     * @uses	URL::site
     * @uses	HTML::attributes
     */
    public static function open($action = null, array $attributes = null)
    {
        if ( $action === null )
        {
            // Use the current URI
            $action = HttpIO::current()->uri;
        }

        if ( strpos($action, '://') === false )
        {
            // Make the URI absolute
            $action = Core::url($action);
        }

        // Add the form action to the attributes
        $attributes['action'] = $action;

        // Only accept the default character set
        $attributes['accept-charset'] = Core::$charset;

        if ( ! isset($attributes['method']) )
        {
            // Use POST method
            $attributes['method'] = 'post';
        }

        return '<form' . HTML::attributes($attributes) . '>';
    }

    /**
     * Creates the closing form tag.
     *
     * echo Form::close();
     *
     * @return  string
     */
    public static function close()
    {
        return '</form>';
    }

    /**
     * Creates a form input. If no type is specified, a "text" type input will
     * be returned.
     *
     * echo Form::input('username', $username);
     *
     * @param   string  input name
     * @param   string  input value
     * @param   array   html attributes
     * @return  string
     * @uses	HTML::attributes
     */
    public static function input($name, $value = null, array $attributes = null)
    {
        // Set the input name
        $attributes['name'] = $name;

        // Set the input value
        $attributes['value'] = $value;

        if ( ! isset($attributes['type']) )
        {
            // Default type is text
            $attributes['type'] = 'text';
        }

        if ($attributes['type'] == 'text' && !isset($attributes['min']) && (!$attributes['value'] || preg_match('#^[0-9.]+$#',$attributes['value'])) && (int)$attributes['value']>=0 )
        {
            $attributes['min'] = '0';
        }

        return '<input' . HTML::attributes($attributes) . ' />';
    }

    /**
     * Creates a hidden form input.
     *
     * echo Form::hidden('csrf', $token);
     *
     * @param   string  input name
     * @param   string  input value
     * @param   array   html attributes
     * @return  string
     * @uses	Form::input
     */
    public static function hidden($name, $value = null, array $attributes = null)
    {
        $attributes['type'] = 'hidden';

        return Form::input($name, $value, $attributes);
    }

    /**
     * Creates a password form input.
     *
     * echo Form::password('password');
     *
     * @param   string  input name
     * @param   string  input value
     * @param   array   html attributes
     * @return  string
     * @uses	Form::input
     */
    public static function password($name, $value = null, array $attributes = null)
    {
        $attributes['type'] = 'password';

        return Form::input($name, $value, $attributes);
    }

    /**
     * Creates a file upload form input. No input value can be specified.
     *
     * echo Form::file('image');
     *
     * @param   string  input name
     * @param   array   html attributes
     * @return  string
     * @uses	Form::input
     */
    public static function file($name, array $attributes = null)
    {
        $attributes['type'] = 'file';

        return Form::input($name, null, $attributes);
    }

    /**
     * Creates a checkbox form input.
     *
     * echo Form::checkbox('remember_me', 1, (bool) $remember);
     *
     * @param   string   input name
     * @param   string   input value
     * @param   boolean  checked status
     * @param   array	html attributes
     * @return  string
     * @uses	Form::input
     */
    public static function checkbox($name, $value = null, $checked = false, array $attributes = null)
    {
        $attributes['type'] = 'checkbox';

        if ( $checked === TRUE )
        {
            // Make the checkbox active
            $attributes['checked'] = 'checked';
        }

        return Form::input($name, $value, $attributes);
    }

    /**
     * Creates a radio form input.
     *
     * echo Form::radio('like_cats', 1, $cats);
     * echo Form::radio('like_cats', 0, ! $cats);
     *
     * @param   string   input name
     * @param   string   input value
     * @param   boolean  checked status
     * @param   array	html attributes
     * @return  string
     * @uses	Form::input
     */
    public static function radio($name, $value = null, $checked = false, array $attributes = null)
    {
        $attributes['type'] = 'radio';

        if ( $checked === TRUE )
        {
            // Make the radio active
            $attributes['checked'] = 'checked';
        }

        return Form::input($name, $value, $attributes);
    }

    /**
     * Creates a textarea form input.
     *
     * echo Form::textarea('about', $about);
     *
     * @param   string   textarea name
     * @param   string   textarea body
     * @param   array	html attributes
     * @param   boolean  encode existing HTML characters
     * @return  string
     * @uses	HTML::attributes
     * @uses	HTML::chars
     */
    public static function textarea($name, $body = '', array $attributes = null, $double_encode = false)
    {
        // Set the input name
        $attributes['name'] = $name;

        // Add default rows and cols attributes (required)
        $attributes += array('rows' => 10, 'cols' => 50);

        return '<textarea' . HTML::attributes($attributes) . '>' . HTML::chars($body, $double_encode) . '</textarea>';
    }

    /**
     * Creates a select form input.
     *
     * echo Form::select('country', $countries, $country);
     *
     * @param   string   input name
     * @param   array	available options
     * @param   mixed	selected option string, or an array of selected options
     * @param   array	html attributes
     * @return  string
     * @uses	HTML::attributes
     */
    public static function select($name, array $options = null, $selected = null, array $attributes = null)
    {
        // Set the input name
        $attributes['name'] = $name;

        if ( is_array($selected) )
        {
            // This is a multi-select, god save us!
            $attributes['multiple'] = 'multiple';
        }

        if ( ! is_array($selected) )
        {
            if ( $selected === null )
            {
                // Use an empty array
                $selected = array();
            }
            else
            {
                // Convert the selected options to an array
                $selected = array((string)$selected);
            }
        }

        if ( empty($options) )
        {
            // There are no options
            $options = '';
        }
        else
        {
            foreach ( $options as $value => $name )
            {
                if ( is_array($name) )
                {
                    // Create a new optgroup
                    $group = array('label' => $value);

                    // Create a new list of options
                    $_options = array();

                    foreach ( $name as $_value => $_name )
                    {
                        // Force value to be string
                        $_value = (string)$_value;

                        // Create a new attribute set for this option
                        $option = array('value' => $_value);

                        if ( in_array($_value, $selected) )
                        {
                            // This option is selected
                            $option['selected'] = 'selected';
                        }

                        // Change the option to the HTML string
                        $_options[] = '<option' . HTML::attributes($option) . '>' . HTML::chars($_name, false) . '</option>';
                    }

                    // Compile the options into a string
                    $_options = "\n" . implode("\n", $_options) . "\n";

                    $options[$value] = '<optgroup' . HTML::attributes($group) . '>' . $_options . '</optgroup>';
                }
                else
                {
                    // Force value to be string
                    $value = (string)$value;

                    // Create a new attribute set for this option
                    $option = array('value' => $value);

                    if ( in_array($value, $selected) )
                    {
                        // This option is selected
                        $option['selected'] = 'selected';
                    }

                    // Change the option to the HTML string
                    $options[$value] = '<option' . HTML::attributes($option) . '>' . HTML::chars($name, false) . '</option>';
                }
            }

            // Compile the options into a single string
            $options = "\n" . implode("\n", $options) . "\n";
        }

        return '<select' . HTML::attributes($attributes) . '>' . $options . '</select>';
    }

    /**
     * Creates a submit form input.
     *
     * echo Form::submit(null, 'Login');
     *
     * @param   string   input name
     * @param   string  input value
     * @param   array   html attributes
     * @return  string
     * @uses	Form::input
     */
    public static function submit($name , $value, array $attributes = null)
    {
        $attributes['type'] = 'submit';

        return Form::input($name, $value, $attributes);
    }

    /**
     * Creates a image form input.
     *
     * echo Form::image(null, null, array('src' => 'media/img/login.png'));
     *
     * @param   string   input name
     * @param   string   input value
     * @param   array	html attributes
     * @param   boolean  add index file to URL?
     * @return  string
     * @uses	Form::input
     */
    public static function image($name, $value, array $attributes = null, $index = false)
    {
        if ( ! empty($attributes['src']) )
        {
            if ( strpos($attributes['src'], '://') === false )
            {
                $attributes['src'] = Core::url($attributes['src'] , $index);
            }
        }

        $attributes['type'] = 'image';

        return Form::input($name, $value, $attributes);
    }

    /**
     * Creates a button form input. Note that the body of a button is NOT escaped,
     * to allow images and other HTML to be used.
     *
     * echo Form::button('save', 'Save Profile', array('type' => 'submit'));
     *
     * @param   string  input name
     * @param   string  input value
     * @param   array   html attributes
     * @return  string
     * @uses	HTML::attributes
     */
    public static function button($name, $body, array $attributes = null)
    {
        // Set the input name
        $attributes['name'] = $name;

        return '<button' . HTML::attributes($attributes) . '>' . $body . '</button>';
    }

    /**
     * Creates a form label. Label text is not automatically translated.
     *
     * echo Form::label('username', 'Username');
     *
     * @param   string  target input
     * @param   string  label text
     * @param   array   html attributes
     * @return  string
     * @uses	HTML::attributes
     */
    public static function label($input, $text = null, array $attributes = null)
    {
        if ( $text === null )
        {
            // Use the input name as the text
            $text = ucwords(preg_replace('/\W+/', ' ', $input));
        }

        // Set the label target
        $attributes['for'] = $input;

        return '<label' . HTML::attributes($attributes) . '>' . $text . '</label>';
    }


    /**
     * 时间输入框
     *
     * @param   string  input name
     * @param   string  input value
     * @param   array   html attributes
     * @return  string
     * @uses	Form::input
     * @uses	HTML::attributes
     */
    public static function input_time($name, $value = '', array $attributes = null , $showinput = true)
    {
        $attributes['time'] = true;
        return Form::input_date($name, $value , $attributes , $showinput);
    }

    /**
     * 日期输入框
     *
     * @param   string  input name
     * @param   string  input value
     * @param   array   html attributes
     * @return  string
     * @uses	Form::input
     * @uses	HTML::attributes
     */
    public static function input_date($name, $value = '', array $attributes = null , $showinput = true)
    {
        static $run = null;
        if ( null===$run )
        {
            $run = true;
            $tmpinput = '<link rel="stylesheet" type="text/css" href="'.Core::url('/statics/css/calender.css').'" /><script type="text/javascript">MyQEE.$import("'.Core::url('/statics/js/calender.js').'");</script>';
        }
        else
        {
            $tmpinput = '';
        }
        $thename = $name ? $name : $attributes['name'];
        $attributes['style'] = 'width:'.($attributes['time']?138:75).'px;font-family:Verdana,Helvetica,Arial,sans-serif;font-size:12px;';
        $attributes['id'] or $attributes['id'] = '_calender_' . $thename;
        $input_att = $attributes;
        $input_att['onclick'] = 'showcalender(event,this,' . ($attributes['time'] ? 'true' : 'false') . ');';
        $input_att['onfocus'] = 'showcalender(event, this,' . ($attributes['time'] ? 'true' : 'false') .');if(this.value==\'0000-00-00' . ($attributes['time'] ? ' 0:0:0' : '') . '\')this.value=\'\';';
        $input_att['onmousewheel'] = 'return wheelcalender(event,this,' . ($attributes['time'] ? 'true' : 'false') . ');';
        unset($input_att['time']);

        $tmpinput .= '<span style="white-space:nowrap;">' . Form::input($name, $value == '' ? '' : date("Y-m-d" . ($attributes['time'] ? ' H:i:s' : ''), $value > 0 ? $value : TIME ), $input_att );
        if ( $showinput )
        {
            $tmpinput .= '<img src="'.Core::url('statics/images/icon/calender.png').'" style="margin-right:8px;margin-left:2px;vertical-align:middle;cursor:pointer;" title="显示时间控件" onclick="var myobj=MyQEE.$(\'' . $attributes['id'] .'\');if(myobj){if(myobj.disabled)return false;myobj.focus();myobj.onfocus(event);}" />';
        }
        $tmpinput .= '</span>';

        return $tmpinput;
    }

    /**
     * 输出一个带下拉的input框
     *
     *     // 简单的输出例子
     *     Form::input_select('test', 1, array('a,'b','c'));
     *
     *     // 带JS设置的处理方式
     *     <script>
     *     var set_input = function(obj)
     *     {
     *         obj.url = '/test.php';
     *         obj.method = 'POST';
     *     }
     *     </script>
     *     <?php
     *     Form::input_select('test', 1, array('a,'b','c') , array('size'=>4) , 'set_input');
     *     ?>
     *
     * @param string $name
     * @param string $value
     * @param array $options
     * @param array $attributes
     * @param string $calljs 回调JS方法
     */
    public static function input_select($name, $value = null, $options = array(), array $attributes = null , $calljs = null )
    {
        if (!is_array($attributes))
        {
            $attributes = array();
        }
        if (!is_array($options))
        {
            $options = array();
        }

        $attributes['_is_inputselect'] = 'true';
        $attributes['onclick'] = 'if (!this._o){this._o = new MyQEE.suggest(this);this._o.options = '.json_encode($options).';this._o.correction_left = 1;this._o.correction_top = 2;this._o.correction_width = 2;this._o.correction_height = 3;'.($calljs?'try{'.$calljs.'(this._o);}catch(e){}':'').';this.onfocus();}'.($attributes['onclick']?$attributes['onclick']:'');

        $attributes2 = array(
            '_is_inputselect_show' => 'true',
            'onclick' => 'this.style.display=\'none\';var obj=this.nextSibling;if(obj && obj.getAttribute(\'_is_inputselect\')==\'true\'){obj.style.display=\'\';obj.focus();obj.onclick();}',
        );
        if (isset($attributes['size']))$attributes2['size'] = $attributes['size'];
        if (isset($attributes['style']))$attributes2['style'] = $attributes['style'];
        $attributes['style'] = 'display:none;' . $attributes['style'];
        return Form::input(null , $options[$value] , $attributes2 ).Form::input($name , $value , $attributes );
	}

}