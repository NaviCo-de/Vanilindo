<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['brand_name', 'tagline', 'logo_path', 'logo_alt', 'contact_email', 'whatsapp_number', 'secondary_whatsapp_number', 'address', 'instagram_url', 'meta_description'])]
class SiteSetting extends Model {}
