<?php

namespace App\Support;

class CaptainDocumentCollections
{
    public const CRIMINAL_RECORD = 'criminal_record';

    public const MEDICAL_ANALYSIS = 'medical_analysis';

    public const DRIVING_LICENSE_FRONT = 'driving_license_front';

    public const DRIVING_LICENSE_BACK = 'driving_license_back';

    public const NATIONAL_ID_FRONT = 'national_id_front';

    public const NATIONAL_ID_BACK = 'national_id_back';

  /**
     * Spatie media collection keys for captain documents (single file each).
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            self::CRIMINAL_RECORD,
            self::MEDICAL_ANALYSIS,
            self::DRIVING_LICENSE_FRONT,
            self::DRIVING_LICENSE_BACK,
            self::NATIONAL_ID_FRONT,
            self::NATIONAL_ID_BACK,
        ];
    }

    /**
     * Multipart form field names for admin create/update user (Apidog).
     *
     * @return list<string>
     */
    public static function uploadFieldNames(): array
    {
        return self::keys();
    }

    /**
     * @return array<string, string>
     */
    public static function validationRules(): array
    {
        $rules = [];
        foreach (self::keys() as $key) {
            $rules[$key] = ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,pdf', 'max:10240'];
        }

        return $rules;
    }
}
