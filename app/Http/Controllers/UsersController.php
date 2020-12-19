<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Utilities\ApiCall;
use App\Http\Utilities\DBhelper;
use Illuminate\Support\Facades\Log;
use Exception;


class UsersController extends Controller
{
    /**
     * @var ApiCall
     */
    protected $apiCall;
    /**
     * @var DBhelper
     */
    protected $helper;
    /**
     * @var User
     */
    private $model;

    /**
     * CustomersController constructor.
     */
    function __construct(User $model)
    {

        $this->model = $model;


        $this->apiCall = new ApiCall();
        $this->helper = new DBhelper($this->model);
    }

    /**
     * List of all Users
     */
    public function index()
    {
        try {
            $rows =  User::all();

            $this->apiCall->setStatusCode(200);
            $this->apiCall->addData(compact('rows'));
        } catch (Exception  $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        } finally {
             $this->apiCall->makeResponse();
        }

      //return  $this->helper->all($this->model);

    }

    /**
     * ADD New User
     */
    public function add(Request $request)
    {
        try {

            // Start validation
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|max:255',
                'password' => 'required|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[#$^+=!*()@%&]).{6,10}$/',

            ]);
            // Validation fails
            if ($validator->fails()) {
                $error = $validator->errors();
                $this->apiCall->addData(compact('error'));
                $this->apiCall->setStatusCode(400);
                return $this->apiCall->makeResponse();
            } else {
                $row = $request->all();
                $data = User::create($row);
                $row['id'] = $data->id;
                $success = 'success';
                $this->apiCall->setStatusCode(200);
                $this->apiCall->addData(compact('row', 'success'));

                //return $this->helper->add($this->model, $request);
            }
        } catch (Exception  $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        } finally {
              $this->apiCall->makeResponse();
        }
    }




}
