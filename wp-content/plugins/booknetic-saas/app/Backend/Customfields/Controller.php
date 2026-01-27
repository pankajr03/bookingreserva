<?php

namespace BookneticSaaS\Backend\Customfields;

use BookneticSaaS\Models\TenantFormInput;
use BookneticSaaS\Models\TenantFormInputChoice;

class Controller extends \BookneticApp\Providers\Core\Controller
{
    public function index()
    {
        $formInputs = TenantFormInput::orderBy('order_number')->fetchAll();

        foreach ($formInputs as $fKey => $formInput) {
            $formInputs[ $fKey ] = $formInput->toArray();

            if (in_array($formInput['type'], ['select', 'checkbox', 'radio'])) {
                $choicesList = TenantFormInputChoice::where('form_input_id', (int)$formInput['id'])
                                                    ->orderBy('order_number')
                                                    ->fetchAll();

                $formInputs[ $fKey ]['choices'] = [];

                foreach ($choicesList as $choiceInf) {
                    $formInputs[ $fKey ]['choices'][] = [
                        (int) $choiceInf['id'], htmlspecialchars($choiceInf['title'])
                    ];
                }
            }
        }

        $this->view('edit_form', [
            'inputs'	=>	$formInputs ? $formInputs : []
        ]);
    }
}
