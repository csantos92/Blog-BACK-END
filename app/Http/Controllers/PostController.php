<?php

namespace App\Http\Controllers;

use App\Helpers\JwtAuth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;

class PostController extends Controller
{
    public function __construct()
    {
        //Needs authorization
        $this->middleware('api.auth', ['except' => ['index', 'show', 'getImage', 'getPostsByCategory', 'getPostsByUser']]);
    }

    //Send all posts
    public function index()
    {
        $posts = Post::all()->load('category');

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }

    //Send post by ID
    public function show($id)
    {
        $post = Post::find($id)->load('category')
                               ->load('user');

        if (is_object($post)) {

            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post
            ];
        } else {

            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'El post no existe'
            ];
        }

        return response()->json($data, $data['code']);
    }

    //Save a new post
    public function store(Request $request)
    {
        //Get data (POST)
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {

            //Get identified user
            $user = $this->getIdentity($request);

            //Validate data
            $validate = \Validator::make($params_array, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required',
                'image' => 'required'
            ]);

            if ($validate->fails()) {

                $data = [
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'Datos incorrectos'
                ];
            } else {

                //Save post
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;

                $post->save();

                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post
                ];
            }
        } else {

            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'Datos incorrectos'
            ];
        }

        return response()->json($data, $data['code']);
    }

    //Update a post
    public function update($id, Request $request)
    {
        //Get data (POST)
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {

            //Validate data
            $validate = \Validator::make($params_array, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required'
            ]);

            if ($validate->fails()) {

                $data = array(
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Error al actualizar el post'
                );
            } else {

                //Won't update
                unset($params_array['id']);
                unset($params_array['user_id']);
                unset($params_array['created_at']);
                unset($params_array['user']);

                //Get identified user
                $user = $this->getIdentity($request);

                //Get data to update
                $post = Post::where('id', $id)
                    ->where('user_id', $user->sub)
                    ->first();

                if (!empty($post) && is_object($post)) {

                    //Update
                    $post->update($params_array);

                    $data = array(
                        'code' => 200,
                        'status' => 'success',
                        'post' => $params_array
                    );
                } else {

                    $data = array(
                        'code' => 404,
                        'status' => 'error',
                        'message' => 'Error al actualizar'
                    );
                }
            }
        } else {

            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al actualizar el post'
            );
        }

        return response()->json($data, $data['code']);
    }

    //Delete a post
    public function destroy($id, Request $request)
    {
        //Get identified user
        $user = $this->getIdentity($request);

        //Get post that belongs to the identified user
        $post = Post::where('id', $id)
            ->where('user_id', $user->sub)
            ->first();

        if (is_object($post)) {

            //Delete post
            $post->delete();

            //Return message
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post
            ];
        } else {

            //Return message
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'El post no existe'
            ];
        }

        return response()->json($data, $data['code']);
    }

    //Upload image
    public function upload(Request $request)
    {
        //Get image
        $image = $request->file('file0');

        //Validate image
        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);

        //Save image
        if (!$image || $validate->fails()) {

            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir imagen'
            ];
        } else {

            $image_name = time() . $image->getClientOriginalName();

            \Storage::disk('images')->put($image_name, \File::get($image));

            $data = [
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            ];
        }

        return response()->json($data, $data['code']);
    }

    //Send image
    public function getImage($filename)
    {
        //Check if image exists
        $isset = \Storage::disk('images')->exists($filename);

        if ($isset) {

            //Get image
            $file = \Storage::disk('images')->get($filename);

            //Return image
            return new Response(
                $file,
                200
            );
        } else {

            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'La imagen no existe'
            ];
        }

        return response()->json($data, $data['code']);
    }

    //Send posts of a category
    public function getPostsByCategory($id)
    {
        //Get posts by category
        $posts = Post::where('category_id', $id)->get();

        //Return message
        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }

    //Send posts by user
    public function getPostsByUser($id)
    {
        //Get posts by user
        $posts = Post::where('user_id', $id)->get();

        //Return message
        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }

    //Checks token identity
    private function getIdentity(Request $request)
    {
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);

        return $user;
    }
}
