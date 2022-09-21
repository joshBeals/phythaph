<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\SettingsRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

/**
 * Class SettingsCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SettingsCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    
    use ShowOperation {show as traitShow;}

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Settings::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/settings');
        CRUD::setEntityNameStrings('settings', 'settings');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('name');
        // CRUD::column('content');

        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number']); 
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(SettingsRequest::class);

        CRUD::field('name');
        $this->crud->addField([
            'wrapper' => ['class' => 'form-group col-md-12'],
            'name' => 'content',
            'label' => 'Content',
            'type' => 'repeatable',
            'new_item_label' => 'Add Content',
            'subfields' => [
                [
                    'wrapper' => ['class' => 'form-group col-md-12'],
                    'name' => 'question',
                    'type' => 'text',
                    'label' => 'Question',
                ],
                [
                    'wrapper' => ['class' => 'form-group col-md-12'],
                    'name' => 'answer',
                    'type' => 'summernote',
                    'label' => 'Answer',
                ]
            ],
        ]);

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function show($id)
    {
        // custom logic before
        $content = $this->traitShow($id);

        $this->data['entry']->decorate();
        // cutom logic after
        return view("backpack::settings.show", $this->data);

    }
}
