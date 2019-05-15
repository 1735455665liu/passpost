<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\userModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
class UserController extends Controller
{
    //

    public function reg(){
    $file=file_get_contents('php://input');
    $base=base64_decode($file);
    $public=openssl_get_publickey('file://'.storage_path('openssl/public.pem'));
    openssl_public_decrypt($base,$value,$public);
        $data=json_decode($value);
        $arr=[
            'name'=>$data->name,
            'email'=>$data->email,
            'pass'=>$data->pass,
        ];
        $userInfo=userModel::insertGetId($arr);
        if($userInfo){  //添加成功
            $response=[
                'error'=>0,
                'msg'=>'添加成功'
            ];
            die(json_encode($response,JSON_UNESCAPED_UNICODE));
        }else{  //添加失败
            $response=[
                'error'=>40006,
                'msg'=>'添加失败'
            ];
            die(json_encode($response,JSON_UNESCAPED_UNICODE));

        }

    }

    public function login(){
        $file=file_get_contents('php://input');
        $base=base64_decode($file);
        $public=openssl_get_publickey('file://'.storage_path('openssl/public.pem'));
        openssl_public_decrypt($base,$value,$public);
        $data=json_decode($value);
        $arr=[
            'email'=>$data->email,
            'pass'=>$data->pass,
        ];
        $email=userModel::where(['email'=>$arr['email']])->first();
        if($email){ //用户存在
            //验证密码
            if($arr['pass']==$email->pass){
                //把token存入缓存
                $key='app_token';
                $token=$this->getToken($email['id']);
                Redis::set($key,$token);    //存入缓存中
                Redis::expire($key,604800); //设置过期时间
                $response=[
                    'error'=>0,
                    'msg'=>'登录成功',
                    'data'=>[
                        'token'=>$token,
                        'uid'=>$email['id']
                    ],
                ];
                die(json_encode($response,JSON_UNESCAPED_UNICODE));
            }else{
                $response=[
                    'error'=>40005,
                    'msg'=>'密码有误，请重新填写'
                ];
                die(json_encode($response,JSON_UNESCAPED_UNICODE));
            }
        }else{      //用户不存在
            $response=[
                'error'=>40003,
                'msg'=>'用户不存在'
            ];
            die(json_encode($response,JSON_UNESCAPED_UNICODE));
        }

    }
    public function getToken($id){
        return sha1($id.rand(1111,9999).Str::random(10));
    }

}
