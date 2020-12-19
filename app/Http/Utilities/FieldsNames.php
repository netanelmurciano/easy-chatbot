<?php
namespace App\Http\Utilities;

use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use  App\Http\Utilities\ApiCall;
use Exception;
use Illuminate\Http\Request;

class FieldsNames
{
    protected $apiCall;
    function __construct()
    {

        $this->apiCall = new ApiCall();
    }

    /**
     * Get all data
     */
    function convertFieldsNames($request){
        try {
//            $data = [
//                $request['order']['insuranceIfo']['ins_order'] = 'orderNumber',
//                $request['order']['insuranceIfo']['engine_capacity'] = 'engineCapacity',
//                $request['order']['insuranceIfo']['in_type'] = 'type',
//            ];


            $this->apiCall->addData(compact('request'));
        } catch (Exception $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        } finally {
            return $this->apiCall->makeResponse();
        }
    }
}
