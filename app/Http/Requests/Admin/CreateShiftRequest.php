<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateShiftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        // Only administrators can create shifts
        return auth()->guard('admin')->user()->isRole('administrator');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        return [
            'admin_user_id' => 'required|integer|exists:admin_users,id',
            'shift_date' => 'required|date', // Bỏ after_or_equal:today để linh hoạt hơn
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages()
    {
        return [
            'admin_user_id.required' => 'Nhân viên là bắt buộc.',
            'admin_user_id.integer' => 'ID nhân viên phải là số nguyên.',
            'admin_user_id.exists' => 'Nhân viên không tồn tại.',
            'shift_date.required' => 'Ngày ca trực là bắt buộc.',
            'shift_date.date' => 'Ngày ca trực không hợp lệ.',
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