<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Throwable;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Notification;
use App\Models\SessionYear;
use Illuminate\Http\Request;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index()
    {
        if (!Auth::user()->can('notification-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $users = User::where('deleted_at', null)->get();
        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        return view('notification.index', compact('users', 'session_years'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('notification-create')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'send_to' => 'required',
            'title' => 'required',
            'message' => 'required',
            'session_year_id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            $send_to = $request->send_to;
            $type = "custom";
            $image = null;
            $userinfo = null;

            $notification = new Notification();
            $notification->send_to = $send_to;
            $notification->title = $request->title;
            $notification->session_year_id = $request->session_year_id;
            $notification->message = $request->message;
            $notification->type = $type;
            $notification->date = Carbon::now();
            $notification->is_custom = 1;
            if ($send_to == 1) {
                $userinfo = "all";
                // Get all users except Super Admin and School Admin, but only those with device_type android
                $user = User::whereHas('roles', function ($query) {
                    $query->whereNotIn('name', ['Super Admin', 'School Admin']);
                })->where('device_type', 'android')->where('deleted_at', null)->get()->pluck('id');
            } elseif ($send_to == 2) {
                $userinfo = "specific";
                foreach ($request->user_id as $user_id) {
                    if ($user_id != 0) {
                        $user[] = $user_id;
                    }
                }
            } elseif ($send_to == 3) {
                $userinfo = "students";
                $user = User::whereHas('roles', function ($query) {
                    $query->where('name', 'Student');
                })->where('deleted_at', null)->get()->pluck('id');
            } elseif ($send_to == 4) {
                $userinfo = "parents";
                $user = User::whereHas('roles', function ($query) {
                    $query->where('name', 'Parent');
                })->where('deleted_at', null)->get()->pluck('id');
            } else {
                $userinfo = "teachers";
                $user = User::whereHas('roles', function ($query) {
                    $query->where('name', 'Teacher');
                })->where('deleted_at', null)->get()->pluck('id');
            }

            if ($request->hasFile('image')) {
                $notification_image = $request->file('image');
                // made file name with combination of current time
                $file_name = time() . '-' . $notification_image->getClientOriginalName();
                //made file path to store in database
                $file_path = 'notifications/' . $file_name;
                //resized image
                resizeImage($notification_image);
                //stored image to storage/public/parents folder
                $destinationPath = storage_path('app/public/notifications');
                $notification_image->move($destinationPath, $file_name);
                //saved file path to database
                $notification->image = $file_path;
                $image = asset('storage/' . $file_path);
            }

            $title = $request->title;
            $body =  $request->message;
            $type =  $type;
            $notification->save();

            // Only save individual user notifications for specific users
            if ($send_to == 2 && !empty($userinfo)) {
                foreach ($user as $data) {
                    $user_notification = new UserNotification();
                    $user_notification->notification_id = $notification->id;
                    $user_notification->user_id = $data;
                    $user_notification->save();
                }
            }

            // sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);
            sendNotificationToTopic($title, $body, $type, $image, $userinfo);

            $response = array(
                'error' => false,
                'message' => trans('notification_sent_successfully')
            );
        } catch (Throwable $e) {
            $message = trans('error_occurred');
            $exceptionMessage = strtolower((string) $e->getMessage());
            if (str_contains($exceptionMessage, 'file') && str_contains($exceptionMessage, 'image')) {
                $message = trans('please_select_valid_image');
            } elseif (str_contains($exceptionMessage, 'permission') || str_contains($exceptionMessage, 'unauthorized')) {
                $message = trans('no_permission_message');
            }
            $response = array(
                'error' => true,
                'message' => $message,
                'data' => $e
            );
        }
        return response()->json($response);
    }

    public function show()
    {
        if (!Auth::user()->can('notification-list')) {
            $response = array(
                'error' => true,
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        if (isset($_GET['sort']))
            $sort = $_GET['sort'];
        if (isset($_GET['order']))
            $order = $_GET['order'];

        $session_year_id = $_GET['session_year_id'];

        $sql = Notification::where('id', '!=', 0)->where('is_custom', 1)->where('session_year_id', $session_year_id);
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orwhere('name', 'LIKE', "%$search%")
                ->orwhere('title', 'LIKE', "%$search%")
                ->orwhere('date', 'LIKE', "%$search%");
        }
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;

        foreach ($res as $row) {

            $operate = '<a href=' . route('notifications.destroy', $row->id) . ' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id=' . $row->id . '><i class="fa fa-trash"></i></a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['title'] = $row->title;
            $tempRow['type'] = $row->type;
            $tempRow['message'] = $row->message;
            $tempRow['image'] = $row->image;
            $tempRow['date'] = convertDateFormat($row->date, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function destroy($id)
    {
        if (!Auth::user()->can('notification-delete')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        try {
            $data = Notification::findOrFail($id);
            $data->delete();
            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully')
            ];
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e
            );
        }
        return response()->json($response);
    }
}
