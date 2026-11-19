<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PublicLegalController extends Controller
{
    public function privacy(): Response
    {
        return $this->render('privacy', 'Privacy Policy', 'public-privacy.txt');
    }

    public function terms(): Response
    {
        return $this->render('terms', 'Terms of Service', 'public-terms.txt');
    }

    public function dataDeletion(): Response
    {
        return $this->render('data-deletion', 'Data Deletion Instructions', 'public-data-deletion.txt');
    }

    private function render(string $kind, string $title, string $file): Response
    {
        return Inertia::render('Legal/PublicDocument', [
            'document' => [
                'kind' => $kind,
                'title' => $title,
                'content' => file_get_contents(resource_path('legal/'.$file)),
            ],
        ]);
    }
}
