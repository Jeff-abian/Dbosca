<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MasterlistResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            // Identifiers
            'citizen_id'         => $this->citizen_id,
            'user_id'            => $this->user_id,
            'application_id'     => $this->application_id,
            'scid_number'        => $this->scid_number,

            // Personal Information
            'first_name'         => $this->first_name,
            'middle_name'        => $this->middle_name,
            'last_name'          => $this->last_name,
            'suffix'             => $this->suffix,
            'birth_date'         => $this->birth_date,
            'age'                => $this->age,
            'sex'                => $this->sex,
            'civil_status'       => $this->civil_status,
            'citizenship'        => $this->citizenship,
            'birth_place'        => $this->birth_place,

            // Address & Contact
            'address'            => $this->address,
            'barangay'           => $this->barangay,
            'city_municipality'  => $this->city_municipality,
            'district'           => $this->district,
            'province'           => $this->province,
            'email'              => $this->email,
            'contact_number'     => $this->contact_number,

            // Socio-Economic Info
            'living_arrangement' => $this->living_arrangement,
            'is_pensioner'       => (bool) $this->is_pensioner,
            'pension_amount'     => $this->pension_amount,
            'pension_source_gsis' => (bool) $this->pension_source_gsis,
            'pension_source_sss'  => (bool) $this->pension_source_sss,
            'pension_source_afpslai' => (bool) $this->pension_source_afpslai,
            'pension_source_others' => $this->pension_source_others,

            // Income & Support
            'has_permanent_income' => (bool) $this->has_permanent_income,
            'permanent_income_source' => $this->permanent_income_source,
            'has_regular_support' => (bool) $this->has_regular_support,
            'support_type_cash'   => (bool) $this->support_type_cash,
            'support_cash_amount' => $this->support_cash_amount,
            'support_cash_frequency' => $this->support_cash_frequency,
            'support_type_inkind' => (bool) $this->support_type_inkind,
            'support_inkind_details' => $this->support_inkind_details,

            // Health
            'has_illness'        => (bool) $this->has_illness,
            'illness_details'    => $this->illness_details,
            'hospitalized_last_6_months' => (bool) $this->hospitalized_last_6_months,

            // System Status & Files
            'vital_status'       => $this->vital_status,
            'date_of_death'      => $this->date_of_death,
            'registration_type'  => $this->registration_type,
            'id_status'          => $this->id_status,
            
            // Handled Attachments (Loopable format natin kanina)
            'reg_attachments'    => collect(json_decode($this->reg_attachments, true) ?: [])->map(function ($file) {
                return [
                    'filename' => $file['filename'] ?? 'Attachment',
                    'url'      => asset('storage/' . ($file['path'] ?? ''))
                ];
            }),

            // Timestamps
            'registration_date'  => $this->registration_date,
            'date_reviewed'      => $this->date_reviewed,
            'reviewed_by'        => $this->reviewed_by,
            'date_created'       => $this->date_created,
            'last_updated'       => $this->last_updated,
        ];
    }
}