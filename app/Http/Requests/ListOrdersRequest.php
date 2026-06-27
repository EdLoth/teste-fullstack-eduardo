<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'affiliate_id' => 'nullable|integer',
            'status'       => 'nullable|in:pending,approved,cancelled,refunded',
            'date_from'    => 'nullable|date',
            'date_to'      => 'nullable|date|after_or_equal:date_from',
            'min_value'    => 'nullable|numeric|min:0',
            'max_value'    => 'nullable|numeric|min:0',
            'sort_by'      => 'nullable|in:id,total,status,created_at',
            'sort_dir'     => 'nullable|in:asc,desc',
        ];
    }
}