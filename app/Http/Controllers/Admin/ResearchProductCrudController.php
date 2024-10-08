<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ResearchProductRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

/**
 * Class ResearchProductCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ResearchProductCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    
    use CreateOperation {create as traitCreate;}
    use ShowOperation {show as traitShow;}

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\ResearchProduct::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/research-product');
        CRUD::setEntityNameStrings('research item', 'research items');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('id')->type('text')->label('Item ID');
        CRUD::column('category_id');
        CRUD::column('features');
        CRUD::column('prices');
        CRUD::column('created_at');

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
        CRUD::setValidation(ResearchProductRequest::class);

        CRUD::field('category_id');
        CRUD::field('features');
        CRUD::field('prices');

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */
    }

    public function create()
    {
        $this->traitCreate();
        return view("backpack::research_product.create", $this->data);
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->crud->addField([
            'wrapper' => ['class' => 'form-group col-md-12'],
            'name' => 'other_features',
            'label' => 'Other Features',
            'type' => 'repeatable',
            'new_item_label' => 'Add Feature',
            'subfields' => [
                [
                    'wrapper' => ['class' => 'form-group col-md-12'],
                    'name' => 'name',
                    'type' => 'text',
                    'label' => 'Name',
                ],
                [
                    'wrapper' => ['class' => 'form-group col-md-12'],
                    'name' => 'description',
                    'type' => 'summernote',
                    'label' => 'Description',
                ]
            ],
        ]);
    }

    public function show($id)
    {
        // custom logic before
        $content = $this->traitShow($id);

        $this->data['entry']->decorate();
        // cutom logic after
        return view("backpack::research_product.show", $this->data);

    }
}
