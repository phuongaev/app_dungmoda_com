<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GetAvailableShiftsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        // Only administrators can view available shifts
        return auth()->guard('admin')->user()->isRole('administrator');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        return [
            'exclude_id' => 'required|integer|exists:evening_shifts,id',
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages()
    {
        return [
            'exclude_id.required' => 'ID ca trực loại trừ là bắt buộc.',
            'exclude_id.integer' => 'ID ca trực loại trừ phải là số nguyên.',
            'exclude_id.exists' => 'Ca trực loại trừ không tồn tại.',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        abort(403, 'Bạn không có quyền thực hiện hành động này.');
    }
}