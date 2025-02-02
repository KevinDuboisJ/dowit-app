<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrioritySettingResource extends JsonResource
{
    
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        dd($this->resource);
        $this->resource->map(function ($settings, $key) {
            dd($settings->firstWhere('type', 'color')['value']);
            return [
                'priority' => (int) filter_var($this->priority, FILTER_SANITIZE_NUMBER_INT),
                'color' => $settings->firstWhere('type', 'color')['value'] ?? null,
                'value' => $settings->firstWhere('label', 'seconds')['value'] ?? null,
            ];
            
        });

        // Data preview: 
        // const settings = [
        //     { priority: 1, color: '#8CC63F', value: 3600 }, // 1-hour window
        //     { priority: 2, color: '#F7941D', value: 7200 }, // 2-hour window
        //     { priority: 3, color: '#FF0000', value: 11400 }, // 3-hour window
        //     { priority: 4, color: '#000000' }, // 3-hour window
        //   ];
        
    }
}
