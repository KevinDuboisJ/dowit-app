<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Adjust authorization logic as needed
    }

    public function rules()
    {
        return [
            'selectedUsers' => ['array', 'nullable'],
            'selectedUsers.*.label' => ['string'],
            'selectedUsers.*.value' => ['integer'],

            'selectedTeams' => ['array', 'nullable'],
            'selectedTeams.*.label' => ['string'],
            'selectedTeams.*.value' => ['integer'],

            'date' => ['required', 'array'],
            'date.from' => ['required', 'date', 'after_or_equal:today'],

            'announcement' => ['required', 'string', 'min:1'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $hasSelectedUsers = $this->input('selectedUsers') && count($this->input('selectedUsers')) > 0;
            $hasSelectedTeams = $this->input('selectedTeams') && count($this->input('selectedTeams')) > 0;

            if (!$hasSelectedUsers && !$hasSelectedTeams) {
                $validator->errors()->add('selectedUsers', 'At least one of selectedUsers or selectedTeams must be filled in.');
            }
        });
    }

    public function messages()
    {
        return [
            'date.from.after_or_equal' => 'De startdatum moet vandaag of later zijn.',
            'announcement.required' => 'Het veld voor de mededeling is verplicht.',
        ];
    }

    /**
     * Prepare the validated data for storing in the database.
     *
     * @return array
     */
    public function prepareForDatabase()
    {
        $validated = $this->validated();
        return [
            'user_id' => Auth::user()->id,
            'content' => $validated['announcement'],
            'start_date' => isset($validated['date']['from']) ? date('Y-m-d H:m:s', strtotime($validated['date']['from'])) : null,
            'end_date' => isset($validated['date']['to']) ? date('Y-m-d', strtotime($validated['date']['from'])) : null,
            'recipient_users' => $validated['selectedUsers'] ? collect($validated['selectedUsers'])->pluck('value')->toArray() : null,
            'recipient_teams' => $validated['selectedTeams'] ? collect($validated['selectedTeams'] ?? null)->pluck('value')->toArray() : null,
        ];
    }
}
