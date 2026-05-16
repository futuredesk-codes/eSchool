<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Throwable;
use App\Models\FormField;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FormFieldController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    private string $folder;

    public function __construct()
    {
        $this->folder = 'form-fields';
    }
    public function index()
    {
        if (!Auth::user()->can('form-field-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $studentFields = FormField::where('for', 1)->orderBy('rank', 'ASC')->get();
        $parentFields = FormField::where('for', 2)->orderBy('rank', 'ASC')->get();
        $teacherFields = FormField::where('for', 3)->orderBy('rank', 'ASC')->get();
        $onlineRegistrationForm =  FormField::where('for', 4)->orderBy('rank', 'ASC')->get();
        return view('form_fields.index', compact('studentFields','parentFields', 'teacherFields', 'onlineRegistrationForm'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request): JsonResponse
    {
        if (!Auth::user()->can('form-field-create')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(),[
            'name' => 'required|unique:form_fields,name',
            'type' => 'required',
            'for' => 'required|in:1,2,3,4',
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            // dd($request->all());
            $maxRank = FormField::max('rank');

            $data = new FormField();
            $data->name = str_replace(" ","_",$request->name);
            $data->type = $request->type;
            $data->for = $request->for;
            $data->is_required = isset($request->is_required) ? 1 : 0;
            $data->default_values = $request->default_values ? json_encode(str_replace(" ","_",$request->default_values)) : '';
            $data->other = $request->other;
            $data->rank = $maxRank + 1;
            $data->save();

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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request): JsonResponse
    {
        if (!Auth::user()->can('form-field-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'rank';
        $order = 'ASC';

        if (isset($_GET['offset']))
        $offset = $_GET['offset'];
        if (isset($_GET['limit']))
        $limit = $_GET['limit'];

        if (isset($_GET['sort']))
        $sort = $_GET['sort'];
        if (isset($_GET['order']))
        $order = $_GET['order'];

        $sql = FormField::where('id','!=',0);

        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orwhere('name', 'LIKE', "%$search%")
                ->orwhere('rank', 'LIKE', "%$search%")
                ->orwhere('type', 'LIKE', "%$search%")
                ->orwhere('default_values', 'LIKE', "%$search%");
        }
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);

        $res = $sql->get();
        // dd($res);
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;

        $typeMap = [
            'text' => __('Text'),
            'textarea' => __('Textarea'),
            'dropdown' => __('Dropdown'),
            'checkbox' => __('Checkbox'),
            'radio' => __('Radio Button'),
            'file' => __('File Upload'),
        ];
        foreach ($res as $row) {

            $operate = '<a href=' . route('form-fields.update', $row->id) . ' class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id=' . $row->id . ' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '<a href=' . route('form-fields.destroy', $row->id) . ' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id=' . $row->id . '><i class="fa fa-trash"></i></a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['rank'] = $row->rank;
            $tempRow['name'] = str_replace("_"," ",$row->name);
            $tempRow['for'] = $row->for;
            $tempRow['type'] = $typeMap[$row->type] ?? $row->type;
            $tempRow['is_required'] = $row->is_required;
            $tempRow['default_values'] = json_decode($row->default_values, true)?? '';
            $tempRow['other'] = $row->other;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if (!Auth::user()->can('form-field-edit')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(),[
            'edit_name' => 'required|unique:form_fields,name,' . $request->edit_id,
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }

        try {

            $data = FormField::find($request->edit_id);
            $data->name =  str_replace(" ","_",$request->edit_name);
            $data->for = $request->edit_for;
            $data->type = $request->edit_type;
            $data->is_required = isset($request->edit_required) ? 1 : 0;
            $data->default_values = $request->default_values ? json_encode($request->default_values) : '';
            $data->save();
            $response = [
                'error' => false,
                'message' => trans('data_update_successfully')
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!Auth::user()->can('form-field-delete')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        try {
            $data = FormField::findOrFail($id);
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
    public function changeRank(Request $request): JsonResponse
    {
        try {
            $ids = $request->ids;
            $update = [];
            foreach ($ids as $key => $id) {
                $update[] = [
                    'id' => $id,
                    'rank' => ($key + 1)
                ];
            }
            FormField::upsert($update, ['id'], ['rank']);
            $response = [
                'error' => false,
                'message' => trans('Rank Updated Successfully')
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
