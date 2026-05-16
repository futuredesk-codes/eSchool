<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Throwable;
use App\Models\Subject;
use App\Models\OnlineExam;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use Illuminate\Http\Request;
use App\Models\SubjectTeacher;
use App\Models\OnlineExamQuestion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\OnlineExamQuestionAnswer;
use App\Models\OnlineExamQuestionChoice;
use App\Models\OnlineExamQuestionOption;
use App\Models\SessionYear;
use Illuminate\Support\Facades\Validator;

class OnlineExamQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $teacher_id = Auth::user()->teacher->id;

        //get the class and subject according to subject teacher
        $subject_teacher = SubjectTeacher::where('teacher_id', $teacher_id);
        $class_section_id = $subject_teacher->pluck('class_section_id');
        $class_id = ClassSection::whereIn('id', $class_section_id)->pluck('class_id');
        $subject_id = $subject_teacher->pluck('subject_id');

        $classes = ClassSchool::whereIn('id', $class_id)->with('medium', 'streams')->get();
        $all_subjects = Subject::whereIn('id', $subject_id)->get();
        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        return response(view('online_exam.class_questions', compact('classes', 'all_subjects', 'session_years')));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make(
            $request->all(),
            [
                'class_id' => 'required',
                'subject_id' => 'required',
                'question_type' => 'required|in:0,1',
                'question' => 'required_if:question_type,0',
                'option.*' => 'required_if:question_type,0',
                'equestion' => 'required_if:question_type,1',
                'eoption.*' => 'required_if:question_type,1',
                'answer.*' => 'required_if:question_type,0',
                'image' => 'nullable|mimes:jpeg,png,jpg|image|max:3048',
                'session_year_id' => 'required'
            ],
            [
                'question.required_if' => __('question_is_required'),
                'option.*.required_if' => __('all_options_are_required'),
                'equestion.required_if' => __('question_is_required'),
                'eoption.*.required_if' => __('all_options_are_required'),
            ]
        );

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            if ($request->question_type == 1) {
                $class_subject_id = ClassSubject::where(['class_id' => $request->class_id, 'subject_id' => $request->subject_id])->pluck('id')->first();
                $question_store = new OnlineExamQuestion();
                $question_store->class_subject_id = $class_subject_id;
                $question_store->session_year_id = $request->session_year_id;
                $question_store->question_type = $request->question_type;
                $question_store->question = safe_htmlspecialchars($request->equestion);
                $question_store->note = safe_htmlspecialchars($request->note);
                if ($request->hasFile('image')) {
                    $image = $request->file('image');

                    // made file name with combination of current time
                    $file_name = time() . '-' . $image->getClientOriginalName();

                    //made file path to store in database
                    $file_path = 'online-exam-questions/' . $file_name;

                    //resized image
                    resizeImage($image);

                    //stored image to storage/public/online-exam-questions folder
                    $destinationPath = storage_path('app/public/online-exam-questions');
                    $image->move($destinationPath, $file_name);

                    //saved file path to database
                    $question_store->image_url = $file_path;
                }
                $question_store->save();

                // store options
                $options_id = array();
                foreach ($request->eoption as $key => $option) {
                    $question_option_store = new OnlineExamQuestionOption();
                    $question_option_store->question_id = $question_store->id;
                    $question_option_store->option = safe_htmlspecialchars($option);
                    $question_option_store->save();
                    $options_id[$key] = $question_option_store->id;
                }
                foreach ($request->answer as $answer) {
                    foreach ($options_id as $key => $option) {
                        if ($key == $answer) {
                            $question_answer_store = new OnlineExamQuestionAnswer();
                            $question_answer_store->question_id = $question_store->id;
                            $question_answer_store->answer = $options_id[$key];
                            $question_answer_store->save();
                        }
                    }
                }
            } else {
                $class_subject_id = ClassSubject::where(['class_id' => $request->class_id, 'subject_id' => $request->subject_id])->pluck('id')->first();
                $question_store = new OnlineExamQuestion();
                $question_store->class_subject_id = $class_subject_id;
                $question_store->session_year_id = $request->session_year_id;
                $question_store->question_type = $request->question_type;
                $question_store->question = safe_htmlspecialchars($request->question);
                $question_store->note = safe_htmlspecialchars($request->note);
                if ($request->hasFile('image')) {
                    $image = $request->file('image');

                    // made file name with combination of current time
                    $file_name = time() . '-' . $image->getClientOriginalName();

                    //made file path to store in database
                    $file_path = 'online-exam-questions/' . $file_name;

                    //resized image
                    resizeImage($image);

                    //stored image to storage/public/online-exam-questions folder
                    $destinationPath = storage_path('app/public/online-exam-questions');
                    $image->move($destinationPath, $file_name);

                    //saved file path to database
                    $question_store->image_url = $file_path;
                }
                $question_store->save();

                // store options
                $options_id = array();
                foreach ($request->option as $key => $option) {
                    $question_option_store = new OnlineExamQuestionOption();
                    $question_option_store->question_id = $question_store->id;
                    $question_option_store->option = safe_htmlspecialchars($option);
                    $question_option_store->save();
                    $options_id[$key] = $question_option_store->id;
                }
                foreach ($request->answer as $answer) {
                    foreach ($options_id as $key => $option) {
                        if ($key == $answer) {
                            $question_answer_store = new OnlineExamQuestionAnswer();
                            $question_answer_store->question_id = $question_store->id;
                            $question_answer_store->answer = $options_id[$key];
                            $question_answer_store->save();
                        }
                    }
                }
            }
            $response = array(
                'error' => false,
                'message' => trans('data_store_successfully')
            );
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }

    public function show()
    {
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');
        $session_year_id = request('session_year_id');

        // get the data of class_subejct_id on the basis of filter of class and subject
        $class_subject_ids = [];
        if (request()->filled('class_id')) {
            $class_subject_ids = ClassSubject::where('class_id', request('class_id'))->pluck('id');
        }
        if (request()->filled('subject_id')) {
            $class_subject_ids = ClassSubject::where('subject_id', request('subject_id'))->pluck('id');
        }
        if (request()->filled('class_id') && request()->filled('subject_id')) {
            $class_subject_ids = ClassSubject::where([
                'class_id' => request('class_id'),
                'subject_id' => request('subject_id')
            ])->pluck('id');
        }

        if (Auth::user()->hasRole('Teacher')) {
            $teacher_id = Auth::user()->teacher->id;
            $class_section_ids = SubjectTeacher::where('teacher_id', $teacher_id)->pluck('class_section_id');
            $class_ids = ClassSection::whereIn('id', $class_section_ids)->pluck('class_id');
            $class_subject_ids = ClassSubject::whereIn('class_id', $class_ids)->pluck('id');
        }

        $sql = OnlineExamQuestion::with('class_subject', 'options', 'answers')
            ->where('session_year_id', $session_year_id)

            //search queries
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")
                        ->orWhere('question', 'LIKE', "%$search%")
                        ->orWhere('created_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%")
                        ->orWhere('updated_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%")
                        ->orWhereHas('class_subject', function ($q) use ($search) {
                            $q->WhereHas('class', function ($c) use ($search) {
                                $c->where('name', 'LIKE', "%$search%")
                                    ->orWhereHas('medium', function ($m) use ($search) {
                                        $m->where('name', 'LIKE', "%$search%");
                                    });
                            })
                                ->orWhereHas('subject', function ($c) use ($search) {
                                    $c->whereRaw("concat(name,' - ',type) LIKE '%" . $search . "%'");
                                });
                        })
                        ->orWhereHas('options', function ($p) use ($search) {
                            $p->where('option', 'LIKE', "%$search%");
                        });
                });
            });

        //class and subject filter data
        if (!empty($class_subject_ids)) {
            $sql = $sql->whereIn('class_subject_id', $class_subject_ids);
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
        $questionIds = $res->pluck('id')->toArray();
        $questionsWithChoices = OnlineExamQuestionChoice::whereIn('question_id', $questionIds)->pluck('question_id')->unique()->toArray();

        // Student answers link to choices via question_id column (references online_exam_question_choices.id)
        $choiceIds = OnlineExamQuestionChoice::whereIn('question_id', $questionIds)->pluck('id')->toArray();
        $choicesWithStudentAnswers = !empty($choiceIds)
            ? \App\Models\OnlineExamStudentAnswer::whereIn('question_id', $choiceIds)->pluck('question_id')->unique()->toArray()
            : [];
        // Map back from choice IDs to question IDs
        $questionsWithStudentAnswers = !empty($choicesWithStudentAnswers)
            ? OnlineExamQuestionChoice::whereIn('id', $choicesWithStudentAnswers)->pluck('question_id')->unique()->toArray()
            : [];

        foreach ($res as $row) {
            // data for options which not answers
            $answers_id = '';
            $options_not_answers = '';
            $answers_id = OnlineExamQuestionAnswer::where('question_id', $row->id)->pluck('answer');
            $options_not_answers = OnlineExamQuestionOption::whereNotIn('id', $answers_id)->where('question_id', $row->id)->get();

            $relatedData = [];
            if (in_array($row->id, $questionsWithChoices)) {
                $relatedData[] = 'question choices';
            }
            if (in_array($row->id, $questionsWithStudentAnswers)) {
                $relatedData[] = 'student answers';
            }

            $operate = '';
            $operate .= '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id=' . $row->id . ' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '
                <a
                    href="' . route('online-exam-question.destroy', $row->id) . '"
                    class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-question-form"
                    data-id="' . $row->id . '"'
                . (!empty($relatedData) ? " data-related='" . json_encode($relatedData) . "'" : '') .
                '>
                    <i class="fa fa-trash"></i>
                </a>';

            $tempRow['online_exam_question_id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['class_id'] = $row->class_subject->class_id;
            $tempRow['class_name'] = $row->class_subject->class->name . ' - ' . $row->class_subject->class->medium->name . ' ' . ($row->class_subject->class->streams->name ?? '');
            $tempRow['class_subject_id'] = $row->class_subject_id;
            $tempRow['subject_id'] = $row->class_subject->subject_id;
            $tempRow['subject_name'] = $row->class_subject->subject->name . ' - ' . $row->class_subject->subject->type;
            $tempRow['question_type'] = $row->question_type;
            $tempRow['question'] = '';
            $tempRow['options'] = array();
            $tempRow['answers'] = array();
            $tempRow['options_not_answers'] = array();
            if ($row->question_type) {
                $tempRow['question'] = "<div class='equation-editor-inline' contenteditable=false>" . safe_htmlspecialchars_decode($row->question) . "</div>";
                $tempRow['question_row'] = safe_htmlspecialchars_decode($row->question);

                //options data
                $option_data = array();
                foreach ($row->options as $key => $options) {
                    $option_data = array(
                        'id' => $options->id,
                        'option' => "<div class='equation-editor-inline' contenteditable=false>" . safe_htmlspecialchars_decode($options->option) . "</div>",
                        'option_row' => safe_htmlspecialchars_decode($options->option)
                    );
                    $tempRow['options'][] = $option_data;
                }

                // answers data
                $answer_data = array();
                foreach ($row->answers as $answers) {
                    $answer_data = array(
                        'id' => $answers->id,
                        'option_id' => $answers->answer,
                        'answer' => "<div class='equation-editor-inline' contenteditable=false>" . safe_htmlspecialchars_decode($answers->options->option) . "</div>",
                    );
                    $tempRow['answers'][] = $answer_data;
                }


                // options which are not answers
                $no_answers_array = array();
                foreach ($options_not_answers as $no_answers_data) {
                    $no_answers_array = array(
                        'id' => $no_answers_data->id,
                    );
                    $tempRow['options_not_answers'][] = $no_answers_array;
                }
            } else {
                $tempRow['question'] = safe_htmlspecialchars_decode($row->question);

                //options data
                $option_data = array();
                foreach ($row->options as $key => $options) {
                    $option_data = array(
                        'id' => $options->id,
                        'option' => safe_htmlspecialchars_decode($options->option),
                    );
                    $tempRow['options'][] = $option_data;
                }

                //answers data
                $answer_data = array();
                foreach ($row->answers as $key => $answers) {
                    $answer_data = array(
                        'id' => $answers->id,
                        'option_id' => $answers->answer,
                        'answer' => safe_htmlspecialchars_decode($answers->options->option),
                    );
                    $tempRow['answers'][] =  $answer_data;
                }

                // options which are not answers
                $no_answers_array = array();
                foreach ($options_not_answers as $no_answers_data) {
                    $no_answers_array = array(
                        'id' => $no_answers_data->id,
                    );
                    $tempRow['options_not_answers'][] = $no_answers_array;
                }
            }
            $tempRow['image'] = $row->image_url;
            $tempRow['note'] = safe_htmlspecialchars_decode($row->note);
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
    public function update(Request $request, $id)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'edit_class_id' => 'required',
                'edit_subject_id' => 'required',
                'edit_question' => 'required_if:edit_question_type,0',
                'edit_option.*.option' => 'required_if:edit_question_type,0',
                'edit_equestion' => 'required_if:edit_question_type,1',
                'edit_eoption.*.option' => 'required_if:edit_question_type,1',
                'edit_image' => 'nullable|mimes:jpeg,png,jpg|image|max:3048',
            ],
            [
                'edit_question.required_if' => __('question_is_required'),
                'edit_option.*.option.required_if' => __('all_options_are_required'),
                'edit_equestion.required_if' => __('question_is_required'),
                'edit_eoption.*.option.required_if' => __('all_options_are_required'),
            ]
        );
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            if ($request->edit_question_type) {
                $edit_equestion = OnlineExamQuestion::find($request->edit_id);
                $class_subject_id = ClassSubject::where(['class_id' => $request->edit_class_id, 'subject_id' => $request->edit_subject_id])->pluck('id')->first();
                $edit_equestion->class_subject_id = $class_subject_id;
                $edit_equestion->question = safe_htmlspecialchars($request->edit_equestion);

                // image code
                if ($request->hasFile('edit_image')) {
                    if (Storage::disk('public')->exists($edit_equestion->getRawOriginal('image_url'))) {
                        Storage::disk('public')->delete($edit_equestion->getRawOriginal('image_url'));
                    }
                    $image = $request->file('edit_image');

                    // made file name with combination of current time
                    $file_name = time() . '-' . $image->getClientOriginalName();

                    //made file path to store in database
                    $file_path = 'online-exam-questions/' . $file_name;

                    //resized image
                    resizeImage($image);

                    //stored image to storage/public/online-exam-questions folder
                    $destinationPath = storage_path('app/public/online-exam-questions');
                    $image->move($destinationPath, $file_name);

                    //saved file path to database
                    $edit_equestion->image_url = $file_path;
                }
                $edit_equestion->note = safe_htmlspecialchars($request->edit_note);
                $edit_equestion->save();

                $new_options_id = array();
                foreach ($request->edit_eoption as $key => $edit_option_data) {
                    if ($edit_option_data['id']) {
                        $edit_option = OnlineExamQuestionOption::find($edit_option_data['id']);
                        $edit_option->option = safe_htmlspecialchars($edit_option_data['option']);
                        $edit_option->save();
                    } else {
                        $new_option = new OnlineExamQuestionOption();
                        $new_option->question_id = $request->edit_id;
                        $new_option->option = safe_htmlspecialchars($edit_option_data['option']);
                        $new_option->save();
                        $new_options_id['new' . $key] = $new_option->id;
                    }
                }

                // get the all answers in a variable
                $answers_options = $request->edit_answer;

                // add new answers first
                if (isset($request->edit_answer) && !empty($request->edit_answer)) {
                    foreach ($request->edit_answer as $answer) {
                        foreach ($new_options_id as $key => $option) {

                            //compare the new answer value with new option array key
                            if ($key == $answer) {
                                $new_answers = new OnlineExamQuestionAnswer();
                                $new_answers->question_id = $request->edit_id;
                                $new_answers->answer = $option;
                                $new_answers->save();

                                //remove the new options answers from all answers
                                unset($answers_options[array_search($answer, $answers_options)]);
                            }
                        }
                    }
                }


                // add remaining answers
                if (isset($answers_options) && !empty($answers_options)) {
                    foreach ($answers_options as $answer_key => $answer) {
                        $new_answers = new OnlineExamQuestionAnswer();
                        $new_answers->question_id = $request->edit_id;
                        $new_answers->answer = $answer;
                        $new_answers->save();
                    }
                }
            } else {
                $edit_question = OnlineExamQuestion::find($request->edit_id);
                $class_subject_id = ClassSubject::where(['class_id' => $request->edit_class_id, 'subject_id' => $request->edit_subject_id])->pluck('id')->first();
                $edit_question->class_subject_id = $class_subject_id;
                $edit_question->question = safe_htmlspecialchars($request->edit_question);

                // image code
                if ($request->hasFile('edit_image')) {
                    if (Storage::disk('public')->exists($edit_question->getRawOriginal('image_url'))) {
                        Storage::disk('public')->delete($edit_question->getRawOriginal('image_url'));
                    }
                    $image = $request->file('edit_image');

                    // made file name with combination of current time
                    $file_name = time() . '-' . $image->getClientOriginalName();

                    //made file path to store in database
                    $file_path = 'online-exam-questions/' . $file_name;

                    //resized image
                    resizeImage($image);

                    //stored image to storage/public/online-exam-questions folder
                    $destinationPath = storage_path('app/public/online-exam-questions');
                    $image->move($destinationPath, $file_name);

                    //saved file path to database
                    $edit_question->image_url = $file_path;
                }
                $edit_question->note = safe_htmlspecialchars($request->edit_note);
                $edit_question->save();

                $new_options_id = array();
                foreach ($request->edit_options as $key => $edit_option_data) {
                    if ($edit_option_data['id']) {
                        $edit_option = OnlineExamQuestionOption::find($edit_option_data['id']);
                        $edit_option->option = safe_htmlspecialchars($edit_option_data['option']);
                        $edit_option->save();
                    } else {
                        $new_option = new OnlineExamQuestionOption();
                        $new_option->question_id = $request->edit_id;
                        $new_option->option = safe_htmlspecialchars($edit_option_data['option']);
                        $new_option->save();
                        $new_options_id['new' . $key] = $new_option->id;
                    }
                }

                // get the all answers in a variable
                $answers_options = $request->edit_answer;

                // add new answers first
                if (isset($request->edit_answer) && !empty($request->edit_answer)) {
                    foreach ($request->edit_answer as $answer) {
                        foreach ($new_options_id as $key => $option) {

                            //compare the new answer value with new option array key
                            if ($key == $answer) {
                                $new_answers = new OnlineExamQuestionAnswer();
                                $new_answers->question_id = $request->edit_id;
                                $new_answers->answer = $option;
                                $new_answers->save();

                                //remove the new options answers from all answers
                                unset($answers_options[array_search($answer, $answers_options)]);
                            }
                        }
                    }
                }


                // add remaining answers
                if (isset($answers_options) && !empty($answers_options)) {
                    foreach ($answers_options as $answer_key => $answer) {
                        $new_answers = new OnlineExamQuestionAnswer();
                        $new_answers->question_id = $request->edit_id;
                        $new_answers->answer = $answer;
                        $new_answers->save();
                    }
                }
            }
            $response = array(
                'error' => false,
                'message' => trans('data_update_successfully')
            );
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
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
        if (!Auth::user()->can('manage-online-exam')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message')
            ]);
        }

        try {
            $question = OnlineExamQuestion::findOrFail($id);

            // Cascade handled at model level
            $question->delete();

            return response()->json([
                'error' => false,
                'message' => trans('data_delete_successfully')
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred')
            ]);
        }
    }

    public function removeOptions($id)
    {
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        try {
            OnlineExamQuestionOption::where('id', $id)->delete();
            $response = array(
                'error' => false,
                'message' => trans('data_delete_successfully')
            );
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }
    public function removeAnswers($id)
    {
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        try {
            OnlineExamQuestionAnswer::where('id', $id)->delete();
            $response = array(
                'error' => false,
                'message' => trans('data_delete_successfully')
            );
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }
}
