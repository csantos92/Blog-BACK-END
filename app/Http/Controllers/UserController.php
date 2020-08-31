<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;
use Illuminate\Support\Facades\Redis;

class UserController extends Controller
{
    //Creates a new user
    public function register(Request $request)
    {

        //Get data from users (POST)
        $json = $request->input('json', null);
        $params = json_decode($json); //Object
        $params_array = json_decode($json, true); //Array

        if (!empty($params && $params_array)) {
            //Trim data
            $params_array = array_map('trim', $params_array);

            //Validate data
            $validate = \Validator::make($params_array, [
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users',
                'password'  => 'required'
            ]);

            if ($validate->fails()) {

                //Validation error message
                $data = array(
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'El usuario no se ha creado',
                    'errors'  => $validate->errors()
                );
            } else {

                //Cypher password
                $pwd = hash('sha256', $params->password);

                //Create user
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';

                //Save user
                $user->save();

                // Success message
                $data = array(
                    'status'  => 'success',
                    'code'    => 200,
                    'message' => 'El usuario se ha creado correctamente',
                    'user'    => $user
                );
            }
        } else {
            //Error message
            $data = array(
                'status'  => 'error',
                'code'    => 404,
                'message' => 'Datos enviados incorrectos'
            );
        }

        return response()->json($data, $data['code']);
    }

    //Do login
    public function login(Request $request)
    {

        $jwtAuth = new \JwtAuth();

        //Get data (POST)
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        //Validate data
        $validate = \Validator::make($params_array, [
            'email'     => 'required|email',
            'password'  => 'required'
        ]);

        if ($validate->fails()) {

            //Validation error message
            $signup = array(
                'status'  => 'error',
                'code'    => 404,
                'message' => 'El usuario no se ha podido loguear',
                'errors'  => $validate->errors()
            );
        } else {
            //Cypher pass
            $pwd = hash('sha256', $params->password);

            //Get token
            $signup = $jwtAuth->signup($params->email, $pwd);

            if (!empty($params->getToken)) {
                $signup = $jwtAuth->signup($params->email, $pwd, true);
            }
        }

        return response()->json($signup, 200);
    }

    //Update user info
    public function update(Request $request)
    {

        //Check if user is identified
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        //Get data
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if ($checkToken && !empty($params_array)) {

            //Identified user
            $user = $jwtAuth->checkToken($token, true);

            //Validate data
            $validate = \Validator::make($params_array, [
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users,' . $user->sub
            ]);

            //Fields that won't update
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);

            //Update user in DDBB
            $user_update = User::where('id', $user->sub)->update($params_array);

            //Return result
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user,
                'updated_user' => $params_array
            );
        } else {

            //Error message
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'El usuario no está identificado'
            );
        }

        return response()->json($data, $data['code']);
    }

    //Upload image avatar
    public function upload(Request $request)
    {
        //Get data
        $image = $request->file('file0');

        //Validate data
        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);

        //Save image
        if (!$image || $validate->fails()) {

            //Error message
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir imagen'
            );
        } else {

            $image_name = time() . $image->getClientOriginalName();
            \Storage::disk('users')->put($image_name, \File::get($image));

            $data = array(
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            );
        }

        return response()->json($data, $data['code']);
    }

    //Get user's avatar
    public function getImage($filename)
    {
        $isset = \Storage::disk('users')->exists($filename);

        if ($isset) {
            $file = \Storage::disk('users')->get($filename);
            return new response($file, 200);
        } else {
            //Error message
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al obtener imagen'
            );

            return response()->json($data, $data['code']);
        }
    }

    //Get user by ID
    public function getDetail($id)
    {
        $user = User::find($id);

        if (is_object($user)) {
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user
            );
        } else {
            //Error message
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al obtener usuario'
            );
        }

        return response()->json($data, $data['code']);
    }
}
