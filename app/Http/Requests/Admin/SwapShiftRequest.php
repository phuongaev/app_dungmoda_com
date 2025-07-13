<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SwapShiftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        // Only administrators can swap shifts
        return auth()->guard('admin')->user()->isRole('administrator');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        return [
            'source_id' => 'required|integer|exists:evening_shifts,id',
            'target_id' => [
                'required',
                'integer',
                'exists:evening_shifts,id',
                'different:source_id'
            ],
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages()
    {
        return [
            'source_id.required' => 'ID ca trực nguồn là bắt buộc.',
            'source_id.integer' => 'ID ca trực nguồn phải là số nguyên.',
            'source_id.exists' => 'Ca trực nguồn không tồn tại.',
            'target_id.required' => 'ID ca trực đích là bắt buộc.',
            'target_id.integer' => 'ID ca trực đích phải là số nguyên.',
            'target_id.exists' => 'Ca trực đích không tồn tại.',
            'target_id.different' => 'Không thể hoán đổi ca trực với chính nó.',
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