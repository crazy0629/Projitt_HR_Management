<?php

namespace App\Models\Assessment;

use App\Models\Question\CodingQuestion;
use App\Models\Question\Question;
use Illuminate\Database\Eloquent\Model;

class AssessmentQuestions extends Model
{
    protected $table = 'assessment_questions';

    protected $fillable = [
        'assessment_id',
        'question_id',
        'point',
    ];


    public static function getAssessmentQuestions($assessmentId = null, $typeId = null){


        $assessmentQuestions = AssessmentQuestions::where('assessment_id', $assessmentId)->get();

        if($typeId == 1){
            foreach($assessmentQuestions as $assessment){
                $assessment->question = Question::find($assessment->question_id);
            }
        }else{
            foreach($assessmentQuestions as $assessment){
                $assessment->question = CodingQuestion::find($assessment->question_id);
            }
        }

        $assessmentQuestions->question = $assessmentQuestions;
        return $assessmentQuestions;

    }
    
}
