<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClassSchool;
use App\Models\Mediums;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class MediumController extends Controller
{
    public function index()
    {
        if (!Auth::user()->can('medium-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        return view('medium.index');
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('medium-create')) {
            $response = array(
                'error' => true,
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        $request->validate([
            'name' => 'required'
        ]);
        try {
            $medium = new Mediums();
            $medium->name = $request->name;
            $medium->save();
            $response = [
                'error' => false,
                'message' => trans('data_store_successfully')
            ];
        } catch (\Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }

    public function edit($id)
    {
        $medium = Mediums::find($id);
        return response($medium);
    }

    public function update(Request $request)
    {
        if (!Auth::user()->can('medium-edit')) {
            $response = array(
                'error' => true,
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        $request->validate([
            'name' => 'required'
        ]);
        try {
            $medium = Mediums::find($request->id);
            $medium->name = $request->name;
            $medium->save();
            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }
        return response()->json($response);
    }

    public function destroy($id)
    {
        if (!Auth::user()->can('medium-delete')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            $medium = Mediums::findOrFail($id);

            // Cascade handled at model level
            $medium->delete();

            return response()->json([
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function show()
    {
        if (!Auth::user()->can('medium-list')) {
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

        $sql = Mediums::where('id', '!=', 0);
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")->orwhere('name', 'LIKE', "%$search%");
        }
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;

        // Batch load related data existence to avoid N+1 queries
        $mediumIds = $res->pluck('id')->toArray();
        $mediumsWithClasses = ClassSchool::whereIn('medium_id', $mediumIds)->pluck('medium_id')->unique()->toArray();
        $mediumsWithSubjects = Subject::whereIn('medium_id', $mediumIds)->pluck('medium_id')->unique()->toArray();

        foreach ($res as $row) {
            $relatedData = [];
            if (in_array($row->id, $mediumsWithClasses)) {
                $relatedData[] = 'classes';
            }
            if (in_array($row->id, $mediumsWithSubjects)) {
                $relatedData[] = 'subjects';
            }

            $operate = '<a href=' . route('medium.edit', $row->id) . ' class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id=' . $row->id . ' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '
                <a
                    href="' . route('medium.destroy', $row->id) . '"
                    class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form"
                    data-id="' . $row->id . '"'
                . (!empty($relatedData) ? " data-related='" . json_encode($relatedData) . "'" : '') .
                '>
                    <i class="fa fa-trash"></i>
                </a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->name;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }
}
