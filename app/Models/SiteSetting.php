<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['brand_name', 'tagline', 'logo_path', 'logo_alt', 'contact_email', 'whatsapp_number', 'secondary_whatsapp_number', 'address', 'instagram_url', 'meta_description'])]
class SiteSetting extends Model
{
    public function instagramLink(): ?string
    {
        $url = trim((string) $this->instagram_url);

        return filter_var($url, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
                ? $url
                : null;
    }
}
