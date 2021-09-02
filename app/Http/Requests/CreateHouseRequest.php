<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateHouseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'category_id' => 'required',
            'bedroom' => 'required|min:1',
            'bathroom' => 'required|min:1',
            'address' => 'required',
            'description' => 'required',
            'price' => 'required',
            'status' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Name không được để trống',
            'category_id.required' => 'Không được để trống',
            'bedroom.required' => 'Bedroom không được để trống',
            'bedroom.min' => 'Bedroom có ít nhất 1 phòng',
            'bathroom.required' => 'Bathroom không được để trống',
            'bathroom.min' => 'Bathroom có ít nhất 1 phòng',
            'address.required' => 'Address không được để trống',
            'description.required' => 'Description không được để trống',
            'price.required' => 'Price không được để trống',
            'status.required' => 'không được để trống',
        ];
    }
}
