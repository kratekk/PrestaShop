<?php
/**
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    Dotpay Team <tech@dotpay.pl>
*  @copyright Dotpay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

class DotpayFormHelper
{
    /**
     * Generate form from data array
     * @param array $data
     * @return string
     */
    public static function generate(array $data)
    {
        $html = '';
        foreach ($data['fields'] as $value) {
            switch ($value['type']) {
                case 'select':
                    $html .= self::generateSelect($value)."\n";
                    break;
                case 'button':
                /* --- EMPTY --- */
                case 'reset':
                    $html .= self::generateButton($value)."\n";
                case 'submit':
                    $html .= self::generateSubmit($value)."\n";
                    break;
                case 'hidden':
                    $html .= self::generateDefault($value, true)."\n";
                    break;
                default:
                    $html .= self::generateDefault($value)."\n";
                    break;
            }
        }
        return self::generateFormBegin($data).$html.'</form>';
    }
    
    /**
     * Generate form begin from data array
     * @param array $data
     * @return string
     */
    private static function generateFormBegin(array $data)
    {
        $html = '<form';
        foreach ($data['form'] as $name => $value) {
            $html.=" $name='$value'";
        }
        $html .= '>';
        return $html."\n";
    }
    
    /**
     * Generate default field from data array
     * @param array $data
     * @return string
     */
    private static function generateDefault(array $data, $hidden = false)
    {
        $label = '';
        $llabel = '';
        if (isset($data['label'])) {
            $label = $data['label'];
            unset($data['label']);
        }
        if (isset($data['llabel'])) {
            $llabel = $data['llabel'];
            unset($data['llabel']);
        }
        $html = '<input';
        foreach ($data as $name => $value) {
            $html.=" $name='$value'";
        }
        $html .= ' />';
        $secondClass = ($hidden)?' dotpay-hidden-label':'';
        return '<label class="dotpay-form-label'.$secondClass.'">'.$llabel.$html.$label.'</label>';
    }
    
    /**
     * Generate select field from data array
     * @param array $data
     * @return string
     */
    private static function generateSelect(array $data)
    {
        $label = '';
        $llabel = '';
        if (isset($data['label'])) {
            $label = $data['label'];
            unset($data['label']);
        }
        if (isset($data['llabel'])) {
            $llabel = $data['llabel'];
            unset($data['llabel']);
        }
        $html = '<select';
        foreach ($data as $name => $value) {
            if ($name!='value'&&$name!='values') {
                $html.=" $name='$value'";
            }
        }
        $html .= '>';
        foreach ($data['values'] as $name => $value) {
            if (empty($data['value'])) {
                $data['value'] = null;
            }
            $html.="<option value='$value'".(($value==$data['value'])?" selected":'').">$name</option>";
        }
        $html .= '</select>';
        return '<label>'.$llabel.$html.$label.'</label>';
    }
    
    /**
     * Generate button from data array
     * @param array $data
     * @return string
     */
    private static function generateButton(array $data)
    {
        $label = '';
        $llabel = '';
        if (isset($data['label'])) {
            $label = $data['label'];
            unset($data['label']);
        }
        if (isset($data['llabel'])) {
            $llabel = $data['llabel'];
            unset($data['llabel']);
        }
        $html = '<input';
        foreach ($data as $name => $value) {
            $html.=" $name='$value'";
        }
        $html .= ' />';
        return $llabel.$html.$label;
    }
    
    /**
     * Generate submit from data array
     * @param array $data
     * @return string
     */
    private static function generateSubmit(array $data)
    {
        $label = '';
        $llabel = '';
        if (isset($data['label'])) {
            $label = $data['label'];
            unset($data['label']);
        }
        if (isset($data['llabel'])) {
            $llabel = $data['llabel'];
            unset($data['llabel']);
        }
        $content = '';
        if (isset($data['value'])) {
            $content = $data['value'];
            unset($data['value']);
        }
        $html = '<button';
        foreach ($data as $name => $value) {
            $html.=" $name='$value'";
        }
        $html .= '>'.$content.'</button>';
        return $llabel.$html.$label;
    }
}
