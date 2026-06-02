<?php

namespace App\Services;

use App\Models\ContactUs;
use App\Models\SocialMedia;
use App\Models\InstapayData;

class ContactUsService
{
    public function getAllContactUs()
    {
        // get all contact us , social media , instapay data
        $contactUs = ContactUs::all();
        $socialMedia = SocialMedia::all();
        $instapayData = InstapayData::all();

        return [
            'contact_us' => $contactUs,
            'social_media' => $socialMedia,
            'instapay_data' => $instapayData,
        ];
    }

    public function getContactUsById($type, $id)
    {
        if ($type == 'contact_us') {
            return ContactUs::find($id);
        }
        if ($type == 'social_media') {
            return SocialMedia::find($id);
        }
        if ($type == 'instapay_data') {
            return InstapayData::find($id);
        }
        return false;
    }

    public function createContactUs($data, $type)
    {
        if ($type == 'contact_us') {
            // remove all contact us
            ContactUs::truncate();
            $contactUs = new ContactUs();
            $contactUs->phone = $data['phone'] ?? null;
            $contactUs->email = $data['email'] ?? null;
            $contactUs->save();
            return $contactUs;
        }
        if ($type == 'social_media') {
            // create or update with name   
            $socialMedia = SocialMedia::updateOrCreate(['name' => $data['name']], [
                'url' => $data['url'] ?? null,
                'is_active' => $data['is_active'],
            ]);
            return $socialMedia;
        }
        if ($type == 'instapay_data') {
            $instapayData = InstapayData::updateOrCreate(['number' => $data['number']], [
                'type' => $data['account_type'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            return $instapayData;
        }
        return false;
    }

    public function updateContactUs($data, $type, $id)
    {
        $contactUs = null;
        $socialMedia = null;
        $instapayData = null;

        if ($type == 'contact_us') {
            $contactUs = ContactUs::find($id);
            if ($contactUs) {
                $contactUs->phone = $data['phone'] ?? $contactUs->phone;
                $contactUs->email = $data['email'] ?? $contactUs->email;
                $contactUs->save();
                return $contactUs;
            }else{
                return false;
            }
        }

        if ($type == 'social_media') {
            $socialMedia = SocialMedia::find($id);
            if ($socialMedia) {
                $socialMedia->name = $data['name'] ?? $socialMedia->name;
                $socialMedia->url = $data['url'] ?? $socialMedia->url;
                $socialMedia->is_active = $data['is_active'] ?? $socialMedia->is_active;
                $socialMedia->save();
                return $socialMedia;
            }else{
                return false;
            }
        }
        
        if ($type == 'instapay_data') {
            $instapayData = InstapayData::find($id);
            if ($instapayData) {
                $instapayData->number = $data['number'] ?? $instapayData->number;
                $instapayData->type = $data['account_type'] ?? $instapayData->type;
                $instapayData->is_active = $data['is_active'] ?? $instapayData->is_active;
                $instapayData->save();
                return $instapayData;
            }else{
                return false;
            }
        }
        return false;
    }

    public function destroyContactUs($type, $id)
    {
        if ($type == 'contact_us') {
            $contactUs = ContactUs::find($id);
            if ($contactUs) {
                $contactUs->delete();
                return true;
            }else{
                return false;
            }
        }
        if ($type == 'social_media') {
            $socialMedia = SocialMedia::find($id);
            if ($socialMedia) {
                $socialMedia->delete();
                return true;
            }else{
                return false;
            }
        }
        if ($type == 'instapay_data') {
            $instapayData = InstapayData::find($id);
            if ($instapayData) {
                $instapayData->delete();
                return true;
            }else{
                return false;
            }
        }
        return false;
    }

}