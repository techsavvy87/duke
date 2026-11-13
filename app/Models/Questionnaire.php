<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Questionnaire extends Model
{
    /** Allowed values for the training questionnaire "Previous training" checkboxes. */
    public const PREVIOUS_TRAINING_OPTIONS = [
        'private' => 'Private',
        'group' => 'Group',
        'board_train' => 'Board/Train',
    ];

    protected $fillable = [
        'questions_answers',
    ];

    public function pet()
    {
        return $this->belongsTo(PetProfile::class, 'pet_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    /**
     * Map the legacy "received_training" checkbox values (private, group classes, board, train)
     * to the current "previous_training" option keys.
     */
    public static function legacyPreviousTraining($receivedTraining): array
    {
        if (is_string($receivedTraining)) {
            $receivedTraining = in_array(strtolower(trim($receivedTraining)), ['yes', 'no', ''], true)
                ? []
                : explode(',', $receivedTraining);
        }
        if (!is_array($receivedTraining)) {
            return [];
        }

        $map = [
            'private' => 'private',
            'group' => 'group',
            'group classes' => 'group',
            'board' => 'board_train',
            'train' => 'board_train',
            'board_train' => 'board_train',
        ];

        $result = [];
        foreach ($receivedTraining as $value) {
            $key = $map[strtolower(trim((string) $value))] ?? null;
            if ($key && !in_array($key, $result, true)) {
                $result[] = $key;
            }
        }

        return $result;
    }

    /**
     * Resolve the training-history answers, falling back to the legacy "received_training" array.
     *
     * @return array{has_received_training: ?string, previous_training: array}
     */
    public static function trainingHistory(array $answers): array
    {
        $hasReceived = $answers['has_received_training'] ?? null;
        $previous = $answers['previous_training'] ?? null;

        if ($previous === null) {
            $previous = self::legacyPreviousTraining($answers['received_training'] ?? null);
        }
        $previous = is_array($previous) ? array_values($previous) : [];

        if ($hasReceived === null) {
            $legacy = $answers['received_training'] ?? null;
            if (is_string($legacy) && in_array(strtolower($legacy), ['yes', 'no'], true)) {
                $hasReceived = strtolower($legacy);
            } elseif (!empty($previous)) {
                $hasReceived = 'yes';
            }
        }

        return ['has_received_training' => $hasReceived, 'previous_training' => $previous];
    }

    /**
     * Normalize and validate training questionnaire answers before saving.
     * Accepts either a JSON string or an array and returns the same type.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function prepareTrainingAnswers($questionsAnswers)
    {
        $wasString = is_string($questionsAnswers);
        $answers = $wasString ? json_decode($questionsAnswers, true) : $questionsAnswers;
        if (!is_array($answers)) {
            return $questionsAnswers;
        }

        // Age is free text now (e.g. "8 weeks", "2 years", "Unknown")
        if (array_key_exists('age_when_received', $answers) && $answers['age_when_received'] !== null) {
            $answers['age_when_received'] = trim((string) $answers['age_when_received']);
        }

        // Accept payloads still using the legacy "received_training" format
        $history = self::trainingHistory($answers);
        $answers['has_received_training'] = $history['has_received_training'];
        $answers['previous_training'] = $history['has_received_training'] === 'no' ? [] : $history['previous_training'];

        Validator::make($answers, [
            'has_received_training' => 'nullable|in:yes,no',
            'previous_training' => 'array',
            'previous_training.*' => 'in:' . implode(',', array_keys(self::PREVIOUS_TRAINING_OPTIONS)),
            'age_when_received' => 'nullable|string|max:100',
        ], [], [
            'has_received_training' => 'Has your dog received training before',
            'previous_training' => 'Previous training',
            'previous_training.*' => 'Previous training',
            'age_when_received' => 'How old was your dog when you received them',
        ])->validate();

        return $wasString ? json_encode($answers) : $answers;
    }
}
