<?php
/**
 * Created by PhpStorm.
 * User: jql
 * Date: 03/11/2016
 * Time: 10:19 PM
 */

namespace App\Api\Request;

use App\Api\Requests\Request;

class RegisterRequest extends Request
{
    public function rules()
    {
        return [
            //            'id' => 'required|min:1',
        ];
    }

    public function attributes()
    {
        return [
            'id' => '课程ID',
        ];
    }

    public function custom()
    {
        return [
            'id' => [
                'required' => '缺少ID',
                'min' => '最小值为1',
            ],
        ];
    }
}