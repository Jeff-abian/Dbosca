<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdIssuanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scid_number' => $this->scid_number,
            'citizen_id' => $this->citizen_id,
            'request_details' => [
                'type' => $this->id_request_type,
                'modality' => $this->id_modality,
                'application_date' => $this->application_date?->format('Y-m-d'),
            ],
            'status' => [
                'application_status' => $this->id_application_status,
                'issuance_status' => $this->id_status,
            ],
            'emergency_contact' => [
                'person' => $this->emergency_contact_person,
                'number' => $this->emergency_contact_number,
            ],
            'remarks' => $this->rejection_remarks,
            'dates' => [
                'reviewed' => $this->date_reviewed?->format('Y-m-d'),
                'released' => $this->released_date?->format('Y-m-d'),
                'expiration' => $this->id_expiration_date?->format('Y-m-d'),
            ],
            'photo_url' => $this->photo_url,
            'created_at' => $this->date_created,
            'updated_at' => $this->last_updated,

            // Nested User Details from Masterlist
            'user_details' => new MasterlistResource($this->whenLoaded('masterlist')),
        ];
    }
        
}