<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        // Only administrators can update shifts
        return auth()->guard('admin')->user()->isRole('administrator');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        return [
            'id' => 'required|integer|exists:evening_shifts,id',
            'date' => 'required|date', // Bỏ after_or_equal:today để cho phép di chuyển về quá khứ
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages()
    {
        return [
            'id.required' => 'ID ca trực là bắt buộc.',
            'id.integer' => 'ID ca trực phải là số nguyên.',
            'id.exists' => 'Ca trực không tồn tại.',
            'date.required' => 'Ngày ca trực là bắt buộc.',
            'date.date' => 'Ngày ca trực không hợp lệ.',
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