<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only authenticated users can create orders
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by auth:sanctum middleware
    }

    /**
     * Get the validation rules that apply to the request.
     * Following Input Validation best practices (Security)
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tax' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     * Improves user experience with clear error messages
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tax.required' => 'The tax field is required.',
            'tax.numeric' => 'The tax must be a valid number.',
            'tax.min' => 'The tax must be at least 0.',
            'tax.max' => 'The tax cannot exceed 999,999.99.',
            
            'notes.string' => 'The notes must be a valid text.',
            'notes.max' => 'The notes cannot exceed 1000 characters.',
            
            'items.required' => 'At least one item is required.',
            'items.array' => 'Items must be provided as an array.',
            'items.min' => 'At least one item is required.',
            
            'items.*.product_name.required' => 'Product name is required for each item.',
            'items.*.product_name.string' => 'Product name must be a valid text.',
            'items.*.product_name.max' => 'Product name cannot exceed 255 characters.',
            
            'items.*.quantity.required' => 'Quantity is required for each item.',
            'items.*.quantity.integer' => 'Quantity must be a whole number.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.quantity.max' => 'Quantity cannot exceed 9,999.',
            
            'items.*.unit_price.required' => 'Unit price is required for each item.',
            'items.*.unit_price.numeric' => 'Unit price must be a valid number.',
            'items.*.unit_price.min' => 'Unit price must be at least 0.01.',
            'items.*.unit_price.max' => 'Unit price cannot exceed 999,999.99.',
        ];
    }

    /**
     * Prepare the data for validation.
     * Ensures data is properly formatted before validation
     */
    protected function prepareForValidation(): void
    {
        // Ensure tax is properly formatted
        if ($this->has('tax')) {
            $this->merge([
                'tax' => (float) $this->tax
            ]);
        }

        // Ensure unit prices are properly formatted
        if ($this->has('items')) {
            $items = $this->items;
            foreach ($items as $index => $item) {
                if (isset($item['unit_price'])) {
                    $items[$index]['unit_price'] = (float) $item['unit_price'];
                }
            }
            $this->merge(['items' => $items]);
        }
    }
}
