<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BenefitApplicationResource extends JsonResource
{
    public function toArray($request)
    {
        // Helper function para sa FileController links
        $formatUrl = function($path, $defaultName) {
            if (!$path) return null;
            return url("/api/view-file?path=" . urlencode($path) . "&name=" . urlencode($defaultName) . "&action=view");
        };

        return [
            'id'                 => $this->id,
            'citizen_id'         => $this->citizen_id,
            'first_name'         => $this->first_name,
            'middle_name'        => $this->middle_name,
            'last_name'          => $this->last_name,
            'full_name'          => "{$this->first_name} {$this->last_name}",
            'birth_date'         => $this->birth_date ? $this->birth_date->format('Y-m-d') : null,
            'age'                => $this->age,
            'contact_number'     => $this->contact_number,
            'barangay'           => $this->barangay,
            'city_municipality'  => $this->city_municipality,
            'province'           => $this->province,
            'scid_number'        => $this->scid_number,
            'reg_status'         => $this->reg_status,
            
            // File Links para sa FileController
            'birth_certificate_url'    => $formatUrl($this->birth_certificate, "BCert_{$this->last_name}.pdf"),
            'barangay_certificate_url' => $formatUrl($this->barangay_certificate, "BrgyCert_{$this->last_name}.pdf"),
            'photo_url'                => $formatUrl($this->photo, "Photo_{$this->last_name}.jpg"),
            
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}