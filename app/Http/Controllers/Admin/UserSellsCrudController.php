<?php

namespace App\Http\Controllers\Admin;

use App\Classes\GlobalVars;
use App\Classes\Helper;
use App\Models\User;
use App\Http\Requests\UserSellsRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

/**
 * Class UserSellsCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserSellsCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    // use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
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
        CRUD::setModel(\App\Models\UserSells::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user-sells');
        CRUD::setEntityNameStrings('sell requests', 'sell requests');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('id')->type('text')->label('ID');
        CRUD::column('customer_name')->label("Customer");
        CRUD::column('category_id');
        CRUD::column('type')->type('cleanup');
        CRUD::column('item_features');
        CRUD::column('price')->type('amount');
        CRUD::column('inspection_type')->type('cleanup');
        CRUD::column('inspection_date');
        CRUD::column('status')->type('status');
        CRUD::column('created_at');

        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number']); 
         */
    }

    public function fetchUser()
    {
        return $this->fetch([
            'model' => User::class,
            'paginate' => 10, // items to show per page
            'query' => function ($model) {
                return $model->selectRaw('id, first_name, last_name, email, CONCAT(`first_name`," ",`last_name`, " - ", `email`) as full_name');
                // return $model->active();
            },
            'searchable_attributes' => ['first_name', 'last_name', 'email', 'phone', 'id'],
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(UserSellsRequest::class);

        $this->crud->addField([
            'name' => 'user_id',
            'type' => "select2_from_ajax",
            'ajax' => true,
            'model' => User::class,
            "attribute" => 'name',
            'data_source' => url("api/filter/users"),

            'label' => "Customer",
            'placeholder' => "Search for customer",
            'wrapper' => ['class' => 'form-group col-md-12'],
            'include_all_form_fields' => true,
        ]);
        CRUD::field('category_id');
        CRUD::field('item_features');
        CRUD::field('type');
        CRUD::field('inspection_type');
        CRUD::field('inspection_date');
        CRUD::field('price');

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */
    }

    public function create()
    {
        $this->traitCreate();
        return view("backpack::sell.create", $this->data);
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
        return view("backpack::sell.show", $this->data);

    }
}
