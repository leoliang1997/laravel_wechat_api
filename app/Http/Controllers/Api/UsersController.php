<?php
/**
 * Created by PhpStorm.
 * User: leoliang
 * Date: 2020/4/6
 * Time: 1:56
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;

use App\Models\User;
use EasyWeChat;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function register(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string',
            'identity' => 'required|boolean',
            'code' => 'required|string'
        ]);

        $data = $this->getWeChatInfo($request->code);
        if (isset($data['errcode'])) {
            return error(-1, 'code无效')->setStatusCode(401);
        }

        $user = User::where('openid', $data['openid'])->first();
        if ($user) {
            return error(-2, '请勿重复注册!')->setStatusCode(401);
        }

        $user = User::create([
            'name' => $request->name,
            'identity' => $request->identity,
            'uid' => User::getUid($request->identity),
            'openid' =>  $data['openid'],
            'session_key' => $data['session_key'],
        ]);

        $token = auth('api')->login($user);

        return $this->respondWithToken($token, $user->name, $user->identity);
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'code' => 'required|string'
        ]);

        $data = $this->getWeChatInfo($request->code);
        if (isset($data['errcode'])) {
            return error(-1, 'code无效')->setStatusCode(401);
        }

        /**
         * @var User $user
         */
        $user = User::where('openid', $data['openid'])->first();

        if (!$user) {
            return error(-1, '用户未注册');
        }
        $token = auth('api')->login($user);

        return $this->respondWithToken($token, $user->name, $user->identity);
    }

    public function destroy()
    {
        auth('api')->logout();
        return success([], 0, '退出成功');
    }

    protected function respondWithToken($token, $name, $identity)
    {
        $data = [
            'name' => $name,
            'identity' => $identity,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ];

        return success($data);
    }

    protected function getWeChatInfo($code)
    {
        $miniProgram = EasyWeChat::miniProgram();
        return $miniProgram->auth->session($code);
    }

    public function info(Request $request)
    {
        return success(auth()->getUser()->toArray());
    }
}
