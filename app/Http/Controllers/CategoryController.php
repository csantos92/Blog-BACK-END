<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Category;

class CategoryController extends Controller
{

    public function __construct()
    {
        //Needs authorization
        $this->middleware('api.auth', ['except' => ['index', 'show']]);
    }

    //Sends all categories
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'categories' => $categories
        ]);
    }

    //Show category by ID
    public function show($id)
    {
        $category = Category::find($id);

        if (is_object($category)) {

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'category' => $category
            ]);
        } else {

            return response()->json([
                'code' => 400,
                'status' => 'error',
                'message' => 'La categoría con ese ID no existe'
            ]);
        }
    }

    //Save a new category
    public function store(Request $request)
    {
        //Get data (POST)   
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            //Validate data
            $validate = \Validator::make($params_array, [
                'name' => 'required'
            ]);

            //Save category
            if ($validate->fails()) {
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se ha guardado la categoría'
                ];
            } else {
                $category = new Category();
                $category->name = $params_array['name'];
                $category->save();

                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'category' => $category
                ];
            }
        } else {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'No se ha guardado la categoría'
            ];
        }

        return response()->json($data, $data['code']);
    }

    //Update a category
    public function update($id, Request $request)
    {
        //Get data
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {

            //Validate data
            $validate = \Validator::make($params_array, [
                'name' => 'required'
            ]);

            //Don't want to update
            unset($params_array['id']);
            unset($params_array['created_at']);

            //Update DDBB
            $category = Category::where('id', $id)->update($params_array);

            //Success message
            $data = [
                'code' => 200,
                'status' => 'success',
                'category' => $params_array
            ];
        } else {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'No se ha actualizado la categoría'
            ];
        }

        return response()->json($data, $data['code']);
    }
}
