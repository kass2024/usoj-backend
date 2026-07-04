<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizzesController extends Controller
{
    //
    public function store(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);
        $request->merge(['module_id' => $id]);

        try {
            Quiz::create($request->all());
            return back()->with('message', 'Quiz created successfully.');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }

    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            Quiz::findOrFail($id)->update($request->all());
            return back()->with('message', 'Quiz update successfully.');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
    public function delete($id)
    {
        try {
            Quiz::findOrFail($id)->delete();
            return back()->with('message', 'Quiz deleted successfully.');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function questions($id)
    {
        $quiz = Quiz::findOrFail($id);
        $questions = Question::where('quiz_id', $id)->get();
        return view('lecture.quizzes.questions', compact('quiz', 'questions'));
    }
    public function create($id)
    {
        return view('lecture.quizzes.create', compact('id'));
    }
    public function edit($id)
    {
        $question = Question::findOrFail($id);
        return view('lecture.quizzes.edit', compact('question'));
    }

    public function store_question(Request $request, $quiz_id)
    {
        // Validate question type first
        $request->validate([
            'type' => 'required|in:radio,checkbox,open,file',
            'title' => 'required|string|max:255',
            'marks' => 'required|integer|min:1',
        ]);

        // Additional type-specific validations
        switch ($request->type) {
            case 'radio':
                $request->validate([
                    'choices' => 'required|array|min:2',
                    'choices.*' => 'required|string|max:255',
                    'answers' => 'required|array|size:1', // Must have exactly one answer
                ], [
                    'answers.size' => 'For single choice (radio) questions, please select exactly one correct answer.'
                ]);
                break;

            case 'checkbox':
                $request->validate([
                    'choices' => 'required|array|min:2',
                    'choices.*' => 'required|string|max:255',
                    'answers' => 'required|array|min:2', // Must have at least two answers
                ], [
                    'answers.min' => 'For multiple choice (checkbox) questions, please select at least two correct answers.'
                ]);
                break;

            case 'open':
            case 'file':
                $request->merge(['choices' => null]);
                break;
        }

        try {
            // Prepare choices for multiple choice questions
            $choices = [];
            if (in_array($request->type, ['radio', 'checkbox'])) {
                foreach ($request->choices as $key => $choice) {
                    $choices[] = [
                        'id' => $key,
                        'title' => $choice,
                        'is_correct' => in_array($key, $request->input('answers', [])),
                    ];
                }
            }

            Question::create([
                'title' => $request->title,
                'type' => $request->type,
                'marks' => $request->marks,
                'quiz_id' => $quiz_id,
                'choices' => $choices ?: null,
            ]);

            return redirect()->back()->with('message', 'Question created successfully.');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
    public function update_question(Request $request, $question_id)
    {
        $question = Question::findOrFail($question_id);

        // Validate question type first
        $request->validate([
            'type' => 'required|in:radio,checkbox,open,file',
            'title' => 'required|string|max:255',
            'marks' => 'required|integer|min:1',
        ]);

        // Additional type-specific validations
        switch ($request->type) {
            case 'radio':
                $request->validate([
                    'choices' => 'required|array|min:2',
                    'choices.*' => 'required|string|max:255',
                    'answers' => 'required|array|size:1', // Must have exactly one answer
                ], [
                    'answers.size' => 'For single choice (radio) questions, please select exactly one correct answer.'
                ]);
                break;

            case 'checkbox':
                $request->validate([
                    'choices' => 'required|array|min:2',
                    'choices.*' => 'required|string|max:255',
                    'answers' => 'required|array|min:2', // Must have at least two answers
                ], [
                    'answers.min' => 'For multiple choice (checkbox) questions, please select at least two correct answers.'
                ]);
                break;

            case 'open':
            case 'file':
                $request->merge(['choices' => null]);
                break;
        }

        try {
            // Prepare choices for multiple choice questions
            $choices = [];
            if (in_array($request->type, ['radio', 'checkbox'])) {
                foreach ($request->choices as $key => $choice) {
                    $choices[] = [
                        'id' => $key,
                        'title' => $choice,
                        'is_correct' => in_array($key, $request->input('answers', [])),
                    ];
                }
            }

            $question->update([
                'title' => $request->title,
                'type' => $request->type,
                'marks' => $request->marks,
                'choices' => $choices ?: null,
            ]);

            return redirect()->back()->with('message', 'Question updated successfully.');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
    public function delete_question($id)
    {
        try {
            Question::findOrFail($id)->delete();
            return back()->with('message', 'Question deleted successfully.');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

}
