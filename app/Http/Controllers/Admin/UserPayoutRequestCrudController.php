<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserPayoutRequestRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

/**
 * Class UserPayoutRequestCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserPayoutRequestCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    // use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    // use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    // use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use ShowOperation {show as traitShow;}

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\UserPayoutRequest::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/withdrawal');
        CRUD::setEntityNameStrings('withdrawal', 'withdrawals');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('created_at')->label('Date Created');
        CRUD::column('customer_name')->label('Customer');
        CRUD::column('amount')->type('amount');
        CRUD::column('source');
        CRUD::column('status')->type('status');

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
        return view("backpack::customer.withdrawal", $this->data);

    }

    public function mark_process(Request $request, UserPayoutRequest $id)
    {

        $txn = $id;

        $txn->markAsProcessed();

        $request->session()->flash('success', 'Withdrawal Has Been Processed Successfully');

        return redirect("/withdraw/" . $txn['id'] . "/show");

    }
}
