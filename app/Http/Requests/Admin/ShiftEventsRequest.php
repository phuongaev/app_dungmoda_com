<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ShiftEventsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        return [
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages()
    {
        return [
            'start.required' => 'Ngày bắt đầu là bắt buộc.',
            'start.date' => 'Ngày bắt đầu không hợp lệ.',
            'end.required' => 'Ngày kết thúc là bắt buộc.',
            'end.date' => 'Ngày kết thúc không hợp lệ.',
            'end.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
        ];
    }
}