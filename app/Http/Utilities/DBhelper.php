<?php
/**
 * Created by PhpStorm.
 * User: sogo
 * Date: 18/04/2019
 * Time: 09:35
 */

namespace App\Http\Utilities;

use Illuminate\Support\Facades\DB;
use App\Models\Message;
use App\Models\Mails;
use App\Models\Status;
use App\Models\Department;
use App\Models\Note;
use App\Models\Order;
use App\Models\FileUploader;
use App\Models\PhoneBook;
use App\Models\Lead;
use App\Models\Task;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Innovation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Claims\Custom;
use Tymon\JWTAuth\Exceptions\JWTException;
use  App\Http\Utilities\ApiCall;
use Exception;
use Carbon\Carbon;
use DateTime;


class DBhelper
{
    protected $apiCall;
    protected $model;
    protected $requestUri;
    protected $userType;


    function __construct($model)
    {

        $this->apiCall = new ApiCall();
        $this->model = $model;

        $this->requestUri = explode("/", $_SERVER['REQUEST_URI']);
    }

    /**
     * Get all data
     */
    function all($model)
    {
        try {

            $user = JWTAuth::parseToken()->authenticate();
            $options = $this->getStatuses($options, $model);
            $options['departments'] = $this->getDepartments();

            $rows = $model::orderBy('id', 'DESC')->get();
            // If data is not empty
            if (count($rows) !== 0) {
                $statusesId = [];
                $departmentsId = [];
                $messagesId = [];

                // Get model fills
                $modelFields = $model->getFillable();

                // Handle classAllowedToMassage if exists
                if (in_array('classAllowedToMassage', $modelFields)) {
                    foreach ($rows as $key => $message) {
                        $messagesId[$key] = $message['classAllowedToMassage'];

                        if ($message['classAllowedToMassage']) {
                            $messages = Message::all();
                            $messages_temp_array = array();
                            foreach ($messages as $key => $value) {
                                $data = [
                                    'key' => $value['id'],
                                    'value' => $value['classAllowedToMassage'],
                                    'autoSend' => $value['autoSend'],
                                ];
                                $messages_temp_array[$key] = $data;
                            }
                            $options['allowedToMassage'] = $messages_temp_array;
                        }
                    }
                }

                // Handle leads
                if ($this->requestUri[2] == 'leads') {
                    $options['leadType'] = $model->select('leadType as key', DB::raw('count(*) as count'))
                        ->where('statusId', '=', '1')
                        ->groupBy('leadType')
                        ->get();
                }

                // Renewals counts
                $temp_renewals = [];
                $temp_event = [];
                if ($this->requestUri[2] === 'renewals') {
                    $options['renewals']['insuranceType'] = $temp_renewals;
                    $options['renewals']['companies'] = $temp_event;
                }

                $this->apiCall->setStatusCode(200);
                $this->apiCall->addData(compact('rows', 'options'));
            } else {
                // If data is empty get model fills
                $rows = $model->getFillable();
                // Get all statuses
                $statuses = Status::all();
                $temp_array = array();
                foreach ($statuses as $key => $value) {
                    $data = [
                        'key' => $value['id'],
                        'value' => $value['name'],
                    ];
                    $temp_array[$key] = $data;
                }
                $options['statuses'] = $temp_array;

                // Get all departments
                $departments = Department::all();
                $temp_array = array();
                foreach ($departments as $key => $value) {
                    $data = [
                        'key' => $value['id'],
                        'value' => $value['name'],
                    ];
                    $temp_array[$key] = $data;
                }
                $options['departments'] = $temp_array;

                // Get all messages
                $messages = Message::all();
                $messages_temp_array = array();
                foreach ($messages as $key => $value) {
                    $data = [
                        'key' => $value['id'],
                        'value' => $value['classAllowedToMassage'],
                        'autoSend' => $value['autoSend'],
                    ];
                    $messages_temp_array[$key] = $data;
                }
                $options['allowedToMassage'] = $messages_temp_array;

                $this->apiCall->setStatusCode(200);
                $this->apiCall->addData(compact('rows', 'options'));
            }
        } catch (Exception $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        } finally {
            return $this->apiCall->makeResponse();
        }
    }


    /**
     * Add new data
     */
    function add($model, $request, $status = null)
    {

        try {
            // Get user whose login
            $userType = $this->findAuthenticateUser();

                $row = $request->all();

                if(isset($row['statusId'])) {
                    $row['statusId'] = isset($row['statusId']) ? $row['statusId'] : $status;
                }
                if ($userType['name'] != 'אדמין') {
                    // List of Models which agent can add new rows
                    $models_allows = ['tasks'];
                    if (in_array($this->requestUri[2], $models_allows)) {
                        /*if(isset($row['isShowStatus']) && !empty($row['isShowStatus'])) {
                            $row['isShowStatus'] = 1;
                        } else {
                            $row['isShowStatus'] = 0;
                        }*/
                        $data = $model->create($row);
                        $row['id'] = $data->id;

                        $success = 'success';
                        $this->apiCall->addData(compact('row', 'success'));
                        $this->apiCall->setStatusCode(200);

                    } else {
                        $error = 'only admin user can add a new row';
                        $this->apiCall->addData(compact('row', 'error'));
                        $this->apiCall->setStatusCode(401);
                    }
                } else {
                    /*if(isset($row['tz'])) {
                        $row['tz'] = count($row['tz']) < 9 ? '0'.$row['tz'] : $row['tz'];
                    }*/
                    $data = $model->create($row);
                    $row['id'] = $data->id;

                    $success = 'success';
                    $this->apiCall->addData(compact('row', 'success'));
                    $this->apiCall->setStatusCode(200);
                }



        } catch (Exception $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        } finally {
            return $this->apiCall->makeResponse();
        }

    }

    /**
     * Add new data
     */
    function addHovaLead($model, $request, $status = null)
    {
        try {
            $row = $request->all();
            $row['statusId'] = 1;




            $data = $model->create($row);
            $row['id'] = $data->id;

            $success = 'success';
            $this->apiCall->addData(compact('row', 'success'));
            $this->apiCall->setStatusCode(200);


        } catch (Exception $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        } finally {
            return $this->apiCall->makeResponse();
        }

    }

    /**
     * Update row
     */
    function update($model, $id, $request)
    {
        try {
            // Find user to update
            $row = $model->where('id', '=', $id)->first();
            // User not found
            if (is_null($row)) {
                $this->apiCall->setStatusCode(200);
                $error = ['row not found with id' => $id];
                $this->apiCall->addData(compact('error'));
                // User found
            } else {
                $this->apiCall->setStatusCode(200);
                $rowInfo = $request->input('objectInfo');

                foreach ($rowInfo as $key => $item) {
                    $row[$key] = $item;
                }

                $row->save();

                $success = 'success';
                $this->apiCall->addData(compact('row', 'success'));
            }
        } catch (Exception $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        } finally {
            return $this->apiCall->makeResponse();
        }

    }

    /**
     * Remove row
     */
    public
    function delete($model, $id, $request)
    {
        try {
            // Get the user which connected
            $userType = $this->findAuthenticateUser();

            if ($this->requestUri[2] == 'fileUpload' && $this->requestUri[3] == 'remove') {
                $row = $model::where('id', '=', $id)->first();
                // User not found
                if (is_null($row)) {
                    $this->apiCall->setStatusCode(200);
                    $error = ['row not found with id' => $id];
                    $this->apiCall->addData(compact('error'));
                    // User found
                } else {
                    $this->apiCall->setStatusCode(200);
                    $row->delete();
                    $success = 'success';
                    // The result we returned
                    $this->apiCall->addData(compact('success'));
                }

            } else {
                // Only admin user can delete
                if ($userType['name'] == 'אדמין') {
                    $row = $model::where('id', '=', $id)->first();
                    // User not found
                    if (is_null($row)) {
                        $this->apiCall->setStatusCode(200);
                        $error = ['row not found with id' => $id];
                        $this->apiCall->addData(compact('error'));
                        // User found
                    } else {
                        $this->apiCall->setStatusCode(200);
                        $row->delete();
                        // If we remove order we also remove order products
                        if($this->requestUri[2] == 'orders') {
                            $rows = Product::where('orderId', '=', $id);
                            $rows->delete();
                        }
                        $success = 'success';
                        // The result we returned
                        $this->apiCall->addData(compact('success'));
                    }
                } else {
                    // Return error
                    $error = 'Only admin user can continue';
                    $this->apiCall->addData(compact('error'), 401);
                    return $this->apiCall->makeResponse();
                }
            }

        } catch (Exception $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        } finally {
            return $this->apiCall->makeResponse();
        }
    }

    /**
     * Single user
     */
    public
    function single($model, $id, $request)
    {
        try {
            // Get the user which connected
            $user = JWTAuth::parseToken()->authenticate();

            $options = $this->getStatuses($options, $model);
            $options['departments'] = $this->getDepartments();

            $row = $model::where('id', '=', $id)->first();
            // User not found
            if (is_null($row)) {
                $this->apiCall->setStatusCode(200);
                $error = ['row not found with id' => $id];
                $this->apiCall->addData(compact('error'));
                // User found
            } else {
                // Show notes in customersTable/leadsTable/tasksTable/renewalsTable
                $mails = [];
                $files = [];
                $notes = [];
                $messages = [];
                $customer = [];
                $isCustomerExists = '';
                switch ($this->requestUri[2]) {
                    case "leads":
                        $notes = Note::where('model', '=', 'leads')->
                        where('rowId', '=', $id)->orderBy('id', 'DESC')->get();
                        $mails = Mails::where('modelName', '=', 'leads')->
                        where('rowId', '=', $id)->get();
                        $files = FileUploader::where('model', '=', 'leads')->
                        where('rowId', '=', $id)->orderBy('id', 'DESC')->get();
                        $isCustomerExists = Customer::where('identical-number', '=', $row->tz)
                            ->orwhere('email', '=', $row->email)
                            ->orwhere('mobile-phone-number', '=', $row->phone)
                            ->orwhere('additional-phone-number', '=', $row->phone)
                            ->orderBy('id', 'DESC')->first();

                        // Get all messages according user departments
                        $messages = $this->messagesAccordingModel();
                        // Get all statuses according user
                        //$options = $this->getStatuses();
                        // Get all phoneBook
                        $options['phoneBook'] = PhoneBook::all();

                        $options['leadType'] = Lead::select('leadType as key', DB::raw('count(*) as count'))
                            ->groupBy('leadType')
                            ->get();

                        break;
                    case "clients":
                        // get all customer order
                        $customer['orders'] = Order::where('customerId', '=', $id)->get();
                        // get all customer leads
                        $customer['leads'] = Lead::where('email', '=', $row['email'])->
                        orWhere('tz', '=', $row['identical-number'])->get();
                        // Get all the list messages according user departments
                        $messages = $this->messagesAccordingModel();
                        // Get all mails which sent from clients model
                        $mails = Mails::where('modelName', '=', 'clients')->
                        where('rowId', '=', $id)->get();
                        // Get all notes which sent from clients model
                        $notes = Note::where('model', '=', 'clients')->
                        where('rowId', '=', $id)->get();
                        // Get all files which sent from clients model
                        $files = File::where('model', '=', 'clients')->
                        where('rowId', '=', $id)->get();
                        break;
                    case "tasks":
                        // Get all mails which sent from tasks model
                        $mails = Mails::where('modelName', '=', 'tasks')->
                        where('rowId', '=', $id)->get();
                        // Get all notes which sent from tasks model
                        $notes = Note::where('model', '=', 'tasks')->
                        where('rowId', '=', $id)->orderBy('id', 'DESC')->get();
                        // Get all files which sent from tasks model
                        $files = FileUploader::where('model', '=', 'tasks')->
                        where('rowId', '=', $id)->orderBy('id', 'DESC')->get();
                        // Get all the list messages according user departments
                        $messages = $this->messagesAccordingModel();

                        $options['phoneBook'] = PhoneBook::all();
                        break;
                    case "renewals":
                        $notes = Note::where('model', '=', 'renewalsNotes')->
                        where('rowId', '=', $id)->orderBy('id', 'DESC')->get();
                        break;
                }

                $this->apiCall->setStatusCode(200);
                $success = 'success';
                // The result we returned
                $this->apiCall->addData(compact('success', 'row', 'mails', 'files', 'notes',
                    'messages', 'options', 'customer', 'isCustomerExists'));
            }

        } catch (Exception $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        } finally {
            return $this->apiCall->makeResponse();
        }
    }


    /**
     * Filter by date
     */
    function dateFilter($model, $request)
    {
        try {
            // Start validation
            $validator = Validator::make($request->all(), [
                'startDate' => 'required|string',
                'endDate' => 'required|string',
            ]);

            // Validation fails
            if ($validator->fails()) {
                $error = $validator->errors();
                $this->apiCall->addData(compact('error'));
                return $this->apiCall->setStatusCode(400);
            }

            if($this->requestUri[2] == 'orders') {
                $from = $request->input('startDate') . ' ' . '00:00:00';
                $to = $request->input('endDate') . ' ' . '23:59:59';
                $rows = $model->where('created_at', '>=', $from)
                    ->where('created_at', '<=', $to)
                    ->where('revision', '=', Null)
                    ->get();
            } else {
                $from = $request->input('startDate') . ' ' . '00:00:00';
                $to = $request->input('endDate') . ' ' . '23:59:59';
                $rows = $model->where('created_at', '>=', $from)
                    ->where('created_at', '<=', $to)
                    ->get();
            }


            if (count($rows) === 0) {
                $this->apiCall->setStatusCode(200);
                $error = 'dates not found';
                $this->apiCall->addData(compact('error'));
            } else {
                $this->apiCall->setStatusCode(200);
                $success = 'success';
                // The result we returned
                $this->apiCall->addData(compact('success', 'rows'));
            }

        } catch (Exception $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        } finally {
            return $this->apiCall->makeResponse();
        }

    }

    /**
     * Filter by Month and Year
     */
    public function filterByMonthYear($model)
    {
        try {
            $months = $model->whereBetween('created_at',
                [
                    Carbon::now()->startOfYear(),
                    Carbon::now()->endOfYear()
                ])
                ->orderBy('created_at')
                ->get()
                ->groupBy(function ($val) {
                    return Carbon::parse($val->created_at)->format('m');
                });

            $rows = [];
            $months_array = $months->all();
            for ($i = 1; $i <= 12; $i++) {
                if (array_key_exists((0 . $i), $months_array)) {
                    $dateObj = DateTime::createFromFormat('!m', (0 . $i));
                    $monthName = $dateObj->format('F'); // March
                    $rows[$monthName] = count($months_array[(0 . $i)]);
                } else {
                    $dateObj = DateTime::createFromFormat('!m', $i);
                    $monthName = $dateObj->format('F');
                    $rows[$monthName] = '';
                }
            }

            $this->apiCall->addData(compact('rows'));
            $this->apiCall->setStatusCode(200);
        } catch (Exception $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        } finally {
            return $this->apiCall->makeResponse();
        }
    }

    public function addNote($request, $id, $routeName)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if ($user) {
                $row = Note::create([
                    'user' => $user['firstName'],
                    'userType' => $user['departmentId'],
                    'userId' => $user['id'],
                    'rowId' => $id,
                    'subject' => $request['subject'],
                    'description' => $request['description'],
                    'model' => $routeName,
                    'statusId' => 1
                ]);

                $row['id'] = $row->id;
            }
            $success = 'success';
            $this->apiCall->addData(compact('row', 'success'));
            $this->apiCall->setStatusCode(200);
        } catch (Exception $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        } finally {
            return $this->apiCall->makeResponse();
        }
    }

    public function fileUpload($model, $id, $request)
    {
        try {

            // Get the current month
            $currentMonth = Carbon::now()->format('m');
            // Get the current year
            $currentYear = Carbon::now()->format('Y');
            // Get the file name
            $fileName = $request->file('file')->getClientOriginalName();
            // The file path for storing
            $filePath = 'public/' . '' . $currentYear . '/' . $currentMonth;
            // Store the file and get the full path
            $url = $request->file('file')->store($filePath);
            // Get the login user
            $user = JWTAuth::parseToken()->authenticate();

            //$fileUrl = env('FILE_URL', false);
            //$dir = env('UPLOAD_DIR', false);

            $baseUrl = asset('storage/app/');
            $fullPath = $baseUrl . '/' . $url;

            $row = [
                'title' => isset($request['title']) ? $request['title'] : '',
                'description' => isset($request['description']) ? $request['description'] : '',
                'name' => $fileName,
                'url' => $fullPath,
                'rowId' => $id,
                'model' => $this->requestUri[2],
                'userId' => $user['id'],
                'userName' => $user['firstName'],
            ];

            FileUploader::create($row);

            // Return success
            $success = 'success';
            $this->apiCall->addData(compact('success'));
            $this->apiCall->setStatusCode(200);

        } catch (Exception $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        } finally {
            return $this->apiCall->makeResponse();
        }
    }

    /**
     * @param $model
     * @param $id
     * @param $data
     * @param $fileName
     * @param $url
     * @param $row
     * @return mixed
     */
    public function fileUpdate($model, $id, $data, $fileName, $url, $row)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $row['title'] = isset($data['title']) ? $data['title'] : $row['title'];
            $row['description'] = isset($data['description']) ? $data['description'] : $row['description'];
            $row['name'] = $fileName ? $fileName : $row['name'];
            $row['url'] = $url ? 'https://api.hova.co.il/storage/app/' . '' . $url : $row['url'];
            $row['userId'] = $user['id'];
            $row['userName'] = $user['firstName'];

            $row->save();

            // Return success
            $success = 'success';
            $this->apiCall->addData(compact('success'));
            $this->apiCall->setStatusCode(200);

        } catch (Exception $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        } finally {
            return $this->apiCall->makeResponse();
        }
    }

    /**
     * @param $model
     * @param $request
     * @param $id
     * @return mixed
     */
    public function sendMail($model, $request, $id)
    {
        try {
            // Find the message with the id we get
            $request['messageId'] = isset($request['messageId']) ? $request['messageId'] : $request['id'];
            $message = Message::where('id', '=', $request['messageId'])->first();
            // Find the order with the id we get
            $row = $this->model->where('id', '=', $id)->first();

            $files = isset($request['files']) ? $request['files'] : '';

            // Handle subject short code
            $request['subject']  = $this->subjectShortCodes($request['subject'], $row);
            // Handle content short code
            $request['content'] = $this->contentShortCodes($request['content'], $row);
            // Handle smsContent short code
            $request['smsContent'] = $this->contentShortCodes($request['smsContent'], $row);

            $user = array(
                'files' => $files,
                'user' => 'Hova',
                'address' => isset($request['address']) && !empty($request['address']) ? $request['address'] : $row['email'],
                'subject' => isset($request['subject']) && !empty($request['subject']) ? $request['subject'] : $message['subject'],
                'content' => isset($request['content']) && !empty($request['content']) ? $request['content'] : $message['content'],
                'replayTo' => isset($request['replayTo']) && !empty($request['replayTo']) ? $request['replayTo'] : 'info@hova.co.il'
            );

            try {
                // manual email
                if($request['autoSend'] == '0') {
                    if(is_array($request['address'])) {
                        foreach ($request['address'] as $val) {
                            if($val) {
                                $user['address'] = $val['email'];
                                //$user['replayTo'] = $user['replayTo'];
                                Mail::send('mails.demo', ['user' => $user], function ($m) use ($user) {
                                    $m->from('info@hova.co.il');
                                    $m->to($user['address'])->subject($user['subject']);
                                    $m->bcc('scan.hova@gmail.com')->subject($user['subject']);
                                    $m->replyTo($user['replayTo']);
                                    if (!empty($user['files'])) {
                                        foreach ($user['files'] as $file) {
                                            $m->attach($file['url']);
                                        }
                                    }
                                });
                                // add the email to database
                                $userType = JWTAuth::parseToken()->authenticate();
                                $this->addEmailSend($user, $row, $model, $userType, $message);
                                // Send Sms
                                if($request['isSms']) {
                                    if($val['phone'] && $request['smsContent']) {
                                        $this->sendSms($val['phone'], $request['smsContent']);
                                    }
                                }

                            }
                        }
                    }
                    // if have manual emails and not from phone books
                    if(isset($request['secondMailAddress']) && !is_null($request['secondMailAddress']) && !is_array($request['secondMailAddress'])) {
                        $secondMail = $request['secondMailAddress'];
                        // check if we have comma - mutli email
                        if(strpos($secondMail, ',') !== false) {
                            $explodMails = explode(',' ,$secondMail);
                            foreach ($explodMails as $val) {
                                if($val) {
                                    $user['address'] = $val;
                                    //$user['replayTo'] = $user['replayTo'];
                                    Mail::send('mails.demo', ['user' => $user], function ($m) use ($user) {
                                        $m->from('sherut@hova.co.il', 'Hova');
                                        $m->to($user['address'])->subject($user['subject']);
                                        $m->bcc('scan.hova@gmail.com')->subject($user['subject']);
                                        $m->replyTo($user['replayTo']);
                                        if (!empty($user['files'])) {
                                            foreach ($user['files'] as $file) {
                                                $m->attach($file['url']);
                                            }
                                        }
                                    });
                                    // add the email to database
                                    $userType = JWTAuth::parseToken()->authenticate();
                                    $this->addEmailSend($user, $row, $model, $userType, $message);
                                }
                            }
                        } else {
                            // if we have only one email
                            $user['address'] = $request['secondMailAddress'];
                            Mail::send('mails.demo', ['user' => $user], function ($m) use ($user) {
                                $m->from('sherut@hova.co.il', 'Hova');
                                $m->to($user['address'])->subject($user['subject']);
                                $m->bcc('scan.hova@gmail.com')->subject($user['subject']);
                                $m->replyTo($user['replayTo']);
                                if (!empty($user['files'])) {
                                    foreach ($user['files'] as $file) {
                                        $m->attach($file['url']);
                                    }
                                }
                            });
                            // add the email to database
                            $userType = JWTAuth::parseToken()->authenticate();
                            $this->addEmailSend($user, $row, $model, $userType, $message, $user['address']);
                        }
                    }
                }

                // Automatic email
                if($request['autoSend'] == '1') {
                    Mail::send('mails.demo', ['user' => $user], function ($m) use ($user) {
                        $m->from('sherut@hova.co.il', 'Hova');
                        $m->to($user['address'])->subject($user['subject']);
                        $m->bcc('scan.hova@gmail.com')->subject($user['subject']);
                        $m->replyTo($user['replayTo']);
                        if (!empty($user['files'])) {
                            foreach ($user['files'] as $file) {
                                $m->attach($file['url']);
                            }
                        }
                    });

                    // add the email to database
                    $userType = JWTAuth::parseToken()->authenticate();
                    $this->addEmailSend($user, $row, $model, $userType, $message);

                }

                // Automatic email and sms
                if($request['autoSend'] == '2') {
                    Mail::send('mails.demo', ['user' => $user], function ($m) use ($user) {
                        $m->from('sherut@hova.co.il', 'Hova');
                        $m->to($user['address'])->subject($user['subject']);
                        $m->bcc('scan.hova@gmail.com')->subject($user['subject']);
                        $m->replyTo($user['replayTo']);
                        if (!empty($user['files'])) {
                            foreach ($user['files'] as $file) {
                                $m->attach($file['url']);
                            }
                        }
                    });

                    // Send Sms
                    $phone = $row['mobile-phone-number'] ? $row['mobile-phone-number'] : $row['phone'];
                    if($phone && $request['smsContent']) {
                        $this->sendSms($phone, $request['smsContent']);
                    }

                    // add the email to database
                    $userType = JWTAuth::parseToken()->authenticate();
                    $this->addEmailSend($user, $row, $model, $userType, $message);
                }

                // Sms only
                if($request['autoSend'] == '3') {
                    // Send Sms
                    $phone = $request['smsPhoneNumber'] && !empty($request['smsPhoneNumber']) ? $request['smsPhoneNumber']  : $row['mobile-phone-number'];
                    if($phone && $request['smsContent']) {
                        $this->sendSms($phone, $request['smsContent']);
                    }

                    // add the email to database
                    $userType = JWTAuth::parseToken()->authenticate();
                    $this->addEmailSend($user, $row, $model, $userType, $request, false);

                }

                $success = 'success';
                $this->apiCall->addData(compact('row', 'success'));
                $this->apiCall->setStatusCode(200);

            } catch (Exception $e) {
                Log::error($e);
                $this->apiCall->setStatusCode(500);
            }

        } catch (Exception $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        } finally {
            return $this->apiCall->makeResponse();
        }

    }

    public function subjectShortCodes($text, $row)
    {
        if (preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $text, $matches)) {
            foreach ((array)$matches[1] as $match) {
                if ($match) {
                    $text = \str_replace("[$match]", trim($row[$match]), $text);
                }
            };
        }
        return $text;
    }

    public function contentShortCodes($text, $row)
    {
        if (preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $text, $matches)) {
            foreach ((array)$matches[1] as $match) {
                if ($match) {
                    $text = \str_replace("[$match]", trim($row[$match]), $text);
                }
            };
        }
        return $text;
    }

    public function sendSms($phone, $content)
    {
        try{
            $url = 'http://api.inforu.co.il/inforufrontend/WebInterface/SendMessageByNumber.aspx?';

            $user = array(
                'UserName' => 'samihenn88',
                'Password' => 'hennsami881',
            );
            $params = array(
                'SenderCellNumber' => 'Hova',
                'CellNumber' => $phone,
                'MessageString' => $content,
            );
            $data = array_merge($user, $params);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 0);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $server_output = curl_exec($ch);
            // var_dump($server_output);
            curl_close($ch);

            $result = $server_output;
        } catch (Exception $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        }
    }
    public function addEmailSend($user, $row, $model, $userType, $message, $manualEmail = false) {
        try {
            if(!empty($user)) {
                $row = Mails::create([
                    'address' => $manualEmail ? $manualEmail : $user['address'],
                    'subject' => $user['subject'],
                    'content' => $user['content'],
                    'replayTo' => isset($user['replayTo']) ? $user['replayTo'] : '',
                    'isSms' => isset($message['isSms']) ? $message['isSms'] : '',
                    'smsContent' => isset($message['smsContent']) ? $message['smsContent'] : '',
                    'smsPhoneNumber' => isset($message['smsPhoneNumber']) ? $message['smsPhoneNumber'] : '',
                    //'file' => $message['file'],
                    'autoSend' => isset($message['autoSend']) ? $message['autoSend'] : '',
                    'rowId' => $row['id'],
                    'modelName' => $model,
                    'statusId' => 1,
                    'userId' => $userType['id'],
                ]);
            }
        } catch (Exception $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        } finally {
            return $this->apiCall->makeResponse();
        }


    }

    /**
     *  Upload Csv FIle
     */
    public function csvFile($model, $request)
    {
        try {
            if ($request->isEmptyTable === "true") {
                $model->whereNotNull('id')->delete();
                $path = $request->file('file')->getRealPath();
                $data = array_map('str_getcsv', file($path));

                if (count($data) > 0) {
                    $temp_array = array();
                    foreach ($data as $key => $value) {
                        if ($key != 0) {
                            foreach ($data[0] as $i => $header) {
                                $temp_array[$header] = $value[$i];
                            }
                            $model->create($temp_array);
                        }
                    }

                    $success = 'success';
                    $this->apiCall->addData(compact('success'));
                    $this->apiCall->setStatusCode(200);
                }
            } else {
                $error = 'error';
                $this->apiCall->addData(compact('error'));
            }


        } catch (Exception $e) {
            Log::error($e);
            $this->apiCall->setStatusCode(500);
        } finally {
            return $this->apiCall->makeResponse();
        }
    }

    /**
     * User Authenticate
     */
    function findAuthenticateUser()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $userType = Department::where('id', '=', $user['departmentId'])->first();
        return $userType;
    }

    function getStatuses(&$options, $model)
    {
        // Get all statuses
        $data = Status::orderBy('order', 'ASC')->get();

        $statusCount_array = $this->countStatuses($model);

        foreach ($data as $key => $status) {
            if ($status['statusGroup'] == 2) {
                $data = [
                    'key' => $status['id'],
                    'color' => $status['color'],
                    'value' => $status['name'],
                    'statusGroup' => $status['statusGroup'],
                    'department' => $status['departmentId'],
                    'count' => array_key_exists($status['id'], $statusCount_array) ? $statusCount_array[$status['id']] : 0,
                    'isShowStatus' => $status['isShowStatus']
                ];
                $options['lackOfClaims'][$key] = $data;
            } else if ($status['statusGroup'] == 3) {
                $data = [
                    'key' => $status['id'],
                    'color' => $status['color'],
                    'value' => $status['name'],
                    'statusGroup' => $status['statusGroup'],
                    'department' => $status['departmentId'],
                    'count' => array_key_exists($status['id'], $statusCount_array) ? $statusCount_array[$status['id']] : 0,
                    'isShowStatus' => $status['isShowStatus']
                ];
                $options['protectionStatus'][$key] = $data;
            } else {
                $data = [
                    'key' => $status['id'],
                    'color' => $status['color'],
                    'value' => $status['name'],
                    'statusGroup' => $status['statusGroup'],
                    'department' => $status['departmentId'],
                    'count' => array_key_exists($status['id'], $statusCount_array) ? $statusCount_array[$status['id']] : 0,
                    'isShowStatus' => $status['isShowStatus']
                ];

                $options['statuses'][$key] = $data;
            }
            // Send all statuses
            $options['allStatuses'][$key] = $data;


        }

        return $options;
    }

    function getDepartments()
    {
        $departments = Department::all();
        $temp_array = array();
        foreach ($departments as $key => $value) {
            $data = [
                'key' => $value['id'],
                'value' => $value['name'],
            ];
            $temp_array[$key] = $data;
        }
        $options = $temp_array;

        return $options;
    }

   function messagesAccordingModel($model = false)
   {
       // Get all Messages of each user
       $messages = [];
       $allMessages = Message::all();
       foreach ($allMessages as $key => $value) {
           if ($value['modelAllowedToMassage']) {
               $models = explode(',', $value['modelAllowedToMassage']);
               foreach ($models as $k => $v) {
                   switch ($v) {
                       case 1:
                           $v = 'orders';
                           break;
                       case 2:
                           $v = 'leads';
                           break;
                       case 3:
                           $v = 'clients';
                           break;
                       default:
                           $v = 'renewals';
                   }
                   $currentModel = $model ? $model : $this->requestUri[2];
                   if ($v == $currentModel) {
                       $messages[$key] = $value;
                   } else {
                       continue;
                   }
               }
           } else {
               continue;
           }
       }
       return $messages;
   }

    /*function messagesAccordingDepartments()
    {
        // Get all Messages of each user
        $user = JWTAuth::parseToken()->authenticate();
        $getUserDepartment = $user['departmentId'];
        $temp_department = explode(',', $getUserDepartment);
        $messages = [];
        $allMessages = Message::all();
        foreach ($allMessages as $key => $value) {
            if ($value['departmentAllowedToMassage']) {
                $departments = explode(',', $value['departmentAllowedToMassage']);
                foreach ($departments as $k => $v) {
                    if (in_array($v, $temp_department)) {
                        $messages[$key] = $value;
                    } else {
                        continue;
                    }
                }
            } else {
                continue;
            }
        }
        return $messages;
    }*/

    function countStatuses($model)
    {
        // Get model fill
        $modelFillable = $model->getFillable();
        if (in_array('statusId', $modelFillable)) {
            if($this->requestUri[2] == 'orders') {
                /* orders Status */
                $statusCount['order'] = $model
                    ->select('statusId as key', DB::raw('count(*) as count'))
                    ->where('revision', '=', Null)
                    ->groupBy('statusId')
                    ->get();

                /* Lack Of Claims Status */
                $statusCount['lackOfClaimsStatus'] = $model
                    ->select('lackOfClaimsStatus as key', DB::raw('count(*) as count'))
                    ->where('revision', '=', Null)
                    ->groupBy('lackOfClaimsStatus')
                    ->get();

                /* Protective Status */
                $statusCount['protectiveStatus'] = $model
                    ->select('protectiveStatus as key', DB::raw('count(*) as count'))
                    ->where('revision', '=', Null)
                    ->groupBy('protectiveStatus')
                    ->get();

                $statusCount_array = [];
                foreach ($statusCount['order'] as $k => $v) {
                    $statusCount_array[$v['key']] = $v['count'];
                }
                foreach ($statusCount['lackOfClaimsStatus'] as $k => $v) {
                    $statusCount_array[$v['key']] = $v['count'];
                }
                foreach ($statusCount['protectiveStatus'] as $k => $v) {
                    $statusCount_array[$v['key']] = $v['count'];
                }

            } else {
                $statusCount_array = [];
                $statusCount = $model->select('statusId as key', DB::raw('count(*) as count'))
                    ->groupBy('statusId')->get();
                foreach ($statusCount as $k => $v) {
                    $statusCount_array[$v['key']] = $v['count'];
                }
            }

        } else {
            $statusCount_array = [];
        }
        return $statusCount_array;
    }

    /**
     * @param $string
     * @return mixed
     */
   /* function escape_like($string)
    {
        $search = array('%', '_');
        $replace   = array('\%', '\_');
        return str_replace($search, $replace, $string);
    }*/

}
