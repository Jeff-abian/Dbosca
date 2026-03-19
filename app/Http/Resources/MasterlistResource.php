<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MasterlistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
{
    return [
        'id'             => $this->citizen_id,
        'application_id' => $this->application_id,
        'scid_number'    => $this->scid_number,
        'full_name'      => "{$this->first_name} {$this->middle_name} {$this->last_name} {$this->suffix}",
        'age'            => $this->age,
        'sex'            => $this->sex,
        'barangay'       => $this->barangay,
        'id_status'      => $this->id_status,
        'document'       => $this->document_path,
    ];
}
}
