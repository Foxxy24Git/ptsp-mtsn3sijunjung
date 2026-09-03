<?php

namespace App\Http\Controllers;

use App\Models\Form;

class ApplicationController extends Controller
{
    /** Formulir pengajuan satu layanan. */
    public function create(Form $form)
    {
        abort_unless($form->isPublishedService(), 404);

        $form->load('fields');

        return view('layanan.ajukan', ['layanan' => $form]);
    }
}
