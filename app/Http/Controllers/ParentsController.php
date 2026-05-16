<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Throwable;
use App\Models\User;
use App\Models\Parents;
use App\Models\FormField;
use App\Models\Students;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
// use Intervention\Image\Laravel\Facades\Image; // Removed unused import
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ParentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!Auth::user()->can('parents-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $parentFields = FormField::where('for', 2)->orderBy('rank', 'ASC')->get();

        return view('parents.index', compact('parentFields'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    //    public function store(Request $request) {
    //        if (!Auth::user()->can('parents-create') || !Auth::user()->can('parents-edit')) {
    //            $response = array(
    //                'error' => true,
    //                'message' => trans('no_permission_message')
    //            );
    //            return response()->json($response);
    //        }
    //        $request->validate([
    //            'first_name' => 'required',
    //            'last_name' => 'required',
    //            'gender' => 'required',
    //            'email' => 'required|unique:users,email',
    //            'mobile' => 'required',
    //            'dob' => 'required',
    //        ]);
    //        try {
    //
    //            if (isset($request->user_id) && $request->user_id != '') {
    //                $user = User::find($request->user_id);
    //                if ($request->hasFile('image')) {
    //                    if ($user->image != "" && Storage::disk('public')->exists($user->image)) {
    //                        Storage::disk('public')->delete($user->image);
    //                    }
    //                    $user->image = $request->file('image')->store('parents', 'public');
    //                }
    //            } else {
    //                $user = new User();
    //                if ($request->hasFile('image')) {
    //                    $user->image = $request->file('image')->store('parents', 'public');
    //                } else {
    //                    $user->image = "";
    //                }
    //                $user->password = Hash::make('parents');
    //            }
    //            $user->first_name = $request->first_name;
    //            $user->last_name = $request->last_name;
    //            $user->gender = $request->gender;
    //            $user->current_address = $request->current_address;
    //            $user->permanent_address = $request->permanent_address;
    //            $user->email = $request->email;
    //            $user->mobile = $request->mobile;
    //            $user->dob = date('Y-m-d', strtotime($request->dob));
    //            $user->save();
    //
    //            if (isset($request->id) && $request->id != '') {
    //                $parents = Parents::find($request->id);
    //            } else {
    //                $parents = new Parents();
    //            }
    //            $parents->user_id = $user->id;
    //            $parents->save();
    //
    //            $response = [
    //                'error' => false,
    //                'message' => trans('data_store_successfully')
    //            ];
    //        } catch (Throwable $e) {
    //            $response = array(
    //                'error' => true,
    //                'message' => trans('error_occurred'),
    //                'data' => $e
    //            );
    //        }
    //        return response()->json($response);
    //    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        if (!Auth::user()->can('parents-list')) {
            return response()->json(['message' => trans('no_permission_message')]);
        }

        $offset = $_GET['offset'] ?? 0;
        $limit = $_GET['limit'] ?? 10;
        $sort = $_GET['sort'] ?? 'id';
        $order = $_GET['order'] ?? 'DESC';
        $search = $_GET['search'] ?? null;

        $user = Auth::user();

        // Base query
        $sql = Parents::whereHas('user', function ($query) {
            $query->where('status', 1);
        })->with('user:id,current_address,permanent_address');

        // Search filter
        if (!empty($search)) {
            $sql->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%$search%")
                    ->orWhere('first_name', 'LIKE', "%$search%")
                    ->orWhere('last_name', 'LIKE', "%$search%")
                    ->orWhere('gender', 'LIKE', "%$search%")
                    ->orWhere('email', 'LIKE', "%$search%")
                    ->orWhere('mobile', 'LIKE', "%$search%")
                    ->orWhere('occupation', 'LIKE', "%$search%")
                    ->orWhere('dob', 'LIKE', "%$search%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('id', 'LIKE', "%$search%")
                            ->orWhere('current_address', 'LIKE', "%$search%")
                            ->orWhere('permanent_address', 'LIKE', "%$search%");
                    });
            });
        }

        // Get total and paginated results
        $total = $sql->count();
        $res = $sql->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        // Format JSON data
        $data = getSettings('date_formate');
        $rows = [];
        $no = 1;

        foreach ($res as $row) {
            $operate = '<a class="btn btn-xs btn-gradient-primary btn-rounded btn-icon editdata"
                        data-id="' . $row->id . '"
                        data-url="' . url('parents') . '"
                        title="Edit"
                        data-toggle="modal"
                        data-target="#editModal">
                        <i class="fa fa-edit"></i>
                    </a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['user_id'] = $row->user_id;
            $tempRow['first_name'] = $row->first_name;
            $tempRow['last_name'] = $row->last_name;
            $tempRow['gender'] = $row->gender;
            $tempRow['email'] = $row->email;
            $tempRow['dob'] = date($data['date_formate'], strtotime($row->dob));
            $tempRow['mobile'] = $row->mobile;
            $tempRow['occupation'] = $row->occupation;
            $tempRow['current_address'] = $row->user->current_address ?? '';
            $tempRow['permanent_address'] = $row->user->permanent_address ?? '';
            $tempRow['image'] = $row->image;
            $tempRow['dynamic_field'] = !empty($row->dynamic_fields)
                ? json_decode($row->dynamic_fields)
                : '';
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }


    public function update(Request $request, $id)
    {
        if (!Auth::user()->can('parents-create') || !Auth::user()->can('parents-edit')) {
            $response = array(
                'error' => true,
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        $request->validate([
            'edit_id' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'gender' => 'required',
            'email' => 'required|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix|unique:parents,email,' . $id,
            'mobile' => 'required',
            'dob' => 'required',
        ]);
        try {
            // dd($request->all());
            $parents = Parents::findOrFail($id);

            //checks the unique email in user tabel
            $validator = Validator::make($request->all(), [
                'email' => 'required|unique:users,email,' . $parents->user_id,
            ]);
            if ($validator->fails()) {
                $response = array(
                    'error' => true,
                    'message' => $validator->errors()->first()
                );
                return response()->json($response);
            }
            $formFields = FormField::where('for', 2)->orderBy('rank', 'ASC')->get();
            $data = array();
            $status = 0;
            $i = 0;
            // $dynamic_data = json_decode($parents->dynamic_fields, true);
            $dynamic_data = $parents->dynamic_fields ?? [];
            foreach ($formFields as $form_field) {
                // INPUT TYPE CHECKBOX
                if ($form_field->type == 'checkbox') {
                    if ($status == 0) {
                        $data[] = $request->input('checkbox', []);
                        $status = 1;
                    }
                } else if ($form_field->type == 'file') {
                    // INPUT TYPE FILE
                    $get_file = '';
                    $field = str_replace(" ", "_", $form_field->name);
                    if (!is_null($dynamic_data)) {
                        foreach ($dynamic_data as $field_data) {
                            if (isset($field_data[$field])) { // GET OLD FILE IF EXISTS
                                $get_file = $field_data[$field];
                            }
                        }
                    }
                    $hidden_file_name = $field;

                    if ($request->hasFile($field)) {
                        if ($get_file) {
                            Storage::disk('public')->delete($get_file); // DELETE OLD FILE IF NEW FILE IS SELECT
                        }
                        $data[] = [
                            str_replace(" ", "_", $form_field->name) => $request->file($field)->store('students', 'public')
                        ];
                    } else {
                        if ($request->$hidden_file_name) {
                            $data[] = [
                                str_replace(" ", "_", $form_field->name) => $request->$hidden_file_name
                            ];
                        }
                    }
                } else {
                    $field = str_replace(" ", "_", $form_field->name);
                    $data[] = [
                        str_replace(" ", "_", $form_field->name) => $request->$field
                    ];
                }
            }
            if ($parents->user) {
                $user = $parents->user;
                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->gender = $request->gender;
                $user->current_address = $request->current_address;
                $user->permanent_address = $request->permanent_address;
                $user->email = $request->email;
                $user->mobile = $request->mobile;
                $user->dob = date('Y-m-d', strtotime($request->dob));
                $user->image = $parents->getRawOriginal('image');
                $user->update();
            }
            $parents->first_name = $request->first_name;
            $parents->last_name = $request->last_name;
            $parents->gender = $request->gender;
            $parents->email = $request->email;
            $parents->mobile = $request->mobile;
            $parents->dob = date('Y-m-d', strtotime($request->dob));
            $parents->occupation = $request->occupation;
            $parents->dynamic_fields = $data;
            if ($request->hasFile('image')) {
                if ($parents->image != "" && Storage::disk('public')->exists($parents->getRawOriginal('image'))) {
                    Storage::disk('public')->delete($parents->getRawOriginal('image'));
                }

                $image = $request->file('image');

                // made file name with combination of current time
                $file_name = time() . '-' . $image->getClientOriginalName();

                //made file path to store in database
                $file_path = 'parents/' . $file_name;

                //resized image
                resizeImage($image);

                //stored image to storage/public/parents folder
                $destinationPath = storage_path('app/public/parents');
                $image->move($destinationPath, $file_name);

                //saved file path to database
                $parents->image = $file_path;
            }

            $parents->save();

            $response = [
                'error' => false,
                'message' => trans('data_store_successfully')
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

    public function search(Request $request)
    {
        if ($request->type == "father") {
            $parent = Parents::where(function ($query) use ($request) {
                $query->orWhere('email', 'like', '%' . $request->email . '%')
                    ->orWhere('first_name', 'like', '%' . $request->email . '%')
                    ->orWhere('last_name', 'like', '%' . $request->email . '%');
            })
                ->where('gender', 'Male')->get();
        } elseif ($request->type == "mother") {
            $parent = Parents::where(function ($query) use ($request) {
                $query->orWhere('email', 'like', '%' . $request->email . '%')
                    ->orWhere('first_name', 'like', '%' . $request->email . '%')
                    ->orWhere('last_name', 'like', '%' . $request->email . '%');
            })
                ->where('gender', 'Female')->get();
        } else {
            $parent = Parents::where('email', 'like', '%' . $request->email . '%')
                ->orWhere('first_name', 'like', '%' . $request->email . '%')
                ->orWhere('last_name', 'like', '%' . $request->email . '%')->get();
        }

        if (!empty($parent)) {
            $response = [
                'error' => false,
                'data' => $parent
            ];
        } else {
            $response = [
                'error' => true,
                'message' => trans('no_data_found')
            ];
        }
        return response()->json($response);
    }
}
