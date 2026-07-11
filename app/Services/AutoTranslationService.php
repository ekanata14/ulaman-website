<?php

namespace App\Services;

use Stichoza\GoogleTranslate\GoogleTranslate;

class AutoTranslationService
{
    /**
     * Fills missing locale values in a translation array using Google Translate.
     *
     * @param  array  $data  Expected format: ['id' => '...', 'en' => '...']
     */
    public function fillMissingTranslations(array $data): array
    {
        $id = $data['id'] ?? null;
        $en = $data['en'] ?? null;

        // Indonesian provided, English missing — translate ID → EN
        if (! empty($id) && empty($en)) {
            $data['en'] = GoogleTranslate::trans($id, 'en', 'id');
        }

        // English provided, Indonesian missing — translate EN → ID
        elseif (! empty($en) && empty($id)) {
            $data['id'] = GoogleTranslate::trans($en, 'id', 'en');
        }

        return $data;
    }
}
